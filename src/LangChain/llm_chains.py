# llm_chains.py
from langchain_core.prompts import PromptTemplate
from langchain_core.output_parsers import StrOutputParser
from langchain_openai import ChatOpenAI
from langchain.schema.runnable import RunnablePassthrough, RunnableLambda
import os
import json

SCW_API_KEY = os.getenv("SCW_SECRET_KEY", "SCW_SECRET_KEY")
OPENAI_BASE = os.getenv("OPENAI_BASE", "OPENAI_BASE")

def is_valid_location(text: str) -> bool:
    """Validates if a given string is likely a town, place, or location."""
    non_location_phrases = ["none", "hello", "hi", "what", "how", "when", "why"]
    if text.lower() in non_location_phrases or len(text.split()) > 5:
        return False
    return True

def get_llm():
    """Returns a configured ChatOpenAI instance."""
    return ChatOpenAI(
        api_key=SCW_API_KEY,
        base_url=OPENAI_BASE,
        model="gemma-3-27b-it",
        temperature=0,
        max_tokens=32,
    )

def extract_symfony_message():
    """
    Extracts Content and Type from a serialized SymfonyMessage like O:36:\"Symfony\\Component\\Messenger\\Envelope\":2:{s:44:\"\0Symfony\\Component\\Messenger\\Envelope\0stamps\";a:1:{s:46:\"Symfony\\Component\\Messenger\\Stamp\\BusNameStamp\";a:1:{i:0;O:46:\"Symfony\\Component\\Messenger\\Stamp\\BusNameStamp\":1:{s:55:\"\0Symfony\\Component\\Messenger\\Stamp\\BusNameStamp\0busName\";s:21:\"messenger.bus.default\";}}}s:45:\"\0Symfony\\Component\\Messenger\\Envelope\0message\";O:24:\"App\\Message\\AgentMessage\":2:{s:33:\"\0App\\Message\\AgentMessage\0content\";s:17:\"where is brussels\";s:30:\"\0App\\Message\\AgentMessage\0type\";i:0;}}

    I expect content and type to be returned as a dic.
    """

    llm = get_llm()

    return (
        PromptTemplate.from_template(
            "Unserialize the following SymfonyMessage string and extract the 'content' and 'type' fields. I do not want to see code, just the result"
            "Return the result as a JSON object with 'content' and 'type' keys. Do not format with markdown, just return a plain JSON\n\n"
            "Serialized data:\n{data}"
        )
        | llm
        | StrOutputParser()
    ) 

def create_full_chain():
    """
    Creates a single, unified chain for extraction, validation, and query generation.
    """
    llm = get_llm()
    
    # Define individual prompt-based chains as before
    extract_message_chain = (
        PromptTemplate.from_template(
            "Extract the text value of the 'content' key from the following serialized string. "
            "Return only the extracted text and nothing else. "
            "If no 'content' key is found, return 'None'.\n\n"
            "Serialized data:\n{data}"
        )
        | llm
        | StrOutputParser()
    )

    extract_location_chain = (
        PromptTemplate.from_template(
            "You are a helpful assistant that only extracts location names from user questions. "
            "Your only goal is to identify a town, city, country, or specific landmark. "
            "If the question does not contain a location, return 'None'. "
            "Do not return any other text or explanation.\n\n"
            "Question: \"{message}\"\nLocation:"
        )
        | llm
        | StrOutputParser()
    )

    validate_location_chain = (
        PromptTemplate.from_template(
            "Is the following text a valid town, city, country, or location? "
            "Respond with 'Yes' or 'No' and nothing else.\n\n"
            "Text: \"{location}\"\nResponse:"
        )
        | llm
        | StrOutputParser()
    )

    generate_query_chain = (
        PromptTemplate.from_template(
            "Generate a concise Google search query for the coordinates of {location}. "
            "The query should be formatted as a json with the location for which we need the coordinates and the original query. Do not format the response with markdown, just return a clear trimmed json.\n\nQuery:"
        )
        | llm
        | StrOutputParser()
    )

    # Compose the full chain using LCEL
    full_chain = (
        # Step 1: Pass the input data through and extract the message
        {"extracted_message": extract_message_chain, "original_query": RunnablePassthrough()}
        
        # Step 2: Extract the location from the extracted message
        | RunnableLambda(
            lambda x: {
                "location": extract_location_chain.invoke({"message": x["extracted_message"]}),
                "original_query": x["original_query"],
                "extracted_message": x["extracted_message"]
            }
        )

        # Step 3: Validate the location using a custom function
        | RunnableLambda(
            lambda x: {
                "is_valid": is_valid_location(x["location"]) and validate_location_chain.invoke({"location": x["location"]}).strip().lower() == "yes",
                "location": x["location"],
                "extracted_message": x["extracted_message"],
                "original_query": x["original_query"],
            }
        )

        # Step 4: Generate the final query if validation passed
        | RunnableLambda(
            lambda x: (
                json.dumps({
                    **json.loads(generate_query_chain.invoke({"location": x["location"]}).strip()),
                    "original_message": x["extracted_message"].strip()
                })
                if x["is_valid"]
                else json.dumps({
                    "error": "Validation failed: Location is not valid.",
                    "original_message": x["extracted_message"]
                })
            )
        )

        # Step 5: Parse the final JSON result
        | RunnableLambda(
            lambda x: json.loads(x.strip())
        )
    )

    return full_chain

def extract_and_query(question: str) -> str | None:
    """
    Orchestrates the full chain of extraction, validation, and query generation using the composed chain.
    """
    chain = create_full_chain()
    result = chain.invoke({"data": question, "original_query": question})
    
    if "error" in result:
        print(result["error"])
        return None
    else:
        return json.dumps(result)

if __name__ == "__main__":
    serialized_string = "O:36:\"Symfony\\Component\\Messenger\\Envelope\":2:{s:44:\"\0Symfony\\Component\\Messenger\\Envelope\0stamps\";a:1:{s:46:\"Symfony\\Component\\Messenger\\Stamp\\BusNameStamp\";a:1:{i:0;O:46:\"Symfony\\Component\\Messenger\\Stamp\\BusNameStamp\":1:{s:55:\"\0Symfony\\Component\\Messenger\\Stamp\\BusNameStamp\0busName\";s:21:\"messenger.bus.default\";}}}s:45:\"\0Symfony\\Component\\Messenger\\Envelope\0message\";O:24:\"App\\Message\\AgentMessage\":1:{s:33:\"\0App\\Message\\AgentMessage\0content\";s:32:\"where is brussles\";}}"
    extracted_query = extract_and_query(serialized_string)

    if extracted_query:
        print(f"Generated query: {extracted_query}")
    else:
        print("Could not generate a valid query.")