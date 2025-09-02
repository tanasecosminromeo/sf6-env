# sqs_client.py
import boto3
import os
import sys
import time
import llm_chains  # Change this from relative to absolute import
import json

# AWS credentials handled explicitly
SQS_QUEUE_URL = os.getenv("MESSENGER_TRANSPORT_DSN")            
AWS_REGION = os.getenv("AWS_REGION", "eu-west-1")
AWS_ACCESS_KEY = os.getenv("AWS_ACCESS_KEY")
AWS_SECRET_KEY = os.getenv("AWS_SECRET_KEY")

def consume_sqs():
    """
    Polls an SQS queue for messages and processes each one using the LLM chain.
    """
    if not SQS_QUEUE_URL:
        print("Error: SQS_QUEUE_URL environment variable is not set.")
        sys.exit(1)
        
    if not AWS_ACCESS_KEY or not AWS_SECRET_KEY:
        print("Error: AWS credentials not found. Make sure AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY are set.")
        sys.exit(1)

    print(f"Using AWS Region: {AWS_REGION}")
    sqs = boto3.client(
        "sqs", 
        region_name=AWS_REGION,
        aws_access_key_id=AWS_ACCESS_KEY,
        aws_secret_access_key=AWS_SECRET_KEY
    )
    print(f"Polling SQS queue: {SQS_QUEUE_URL}")

    while True:
        try:
            response = sqs.receive_message(
                QueueUrl=SQS_QUEUE_URL,
                AttributeNames=['All'],
                MessageAttributeNames=['All'],
                MaxNumberOfMessages=10,
                WaitTimeSeconds=20
            )

            if "Messages" in response:
                for message in response['Messages']:
                    user_question = message['Body']

                    unserialized_string = llm_chains.extract_symfony_message().invoke({"data": user_question})
                    try:
                        unserialized_message = json.loads(unserialized_string )
                        print(f"Extracted Agent Message: {unserialized_message}")
                        
                        # Now you can access it as a dictionary
                        if unserialized_message.get('type') == 0:  # TO_LLM
                            print("Processing LLM Message")
                        else:
                            print("Not a LLM Message")
                            continue
                    except json.JSONDecodeError as e:
                        print(f"Error decoding JSON: {e}")
                        print(f"Raw response: {unserialized_string}")

                    try:
                        generated_query = llm_chains.extract_and_query(user_question)

                        if generated_query:
                            print(f"Generated Google Query: {generated_query}")
                        else:
                            print("Could not generate a valid query.")
                        
                        sqs.delete_message(
                            QueueUrl=SQS_QUEUE_URL,
                            ReceiptHandle=message['ReceiptHandle']
                        )
                    except Exception as e:
                        print(f"Error processing message: {e}")
            else:
                print("No new messages. Waiting...")

        except Exception as e:
            print(f"An error occurred: {e}")
            time.sleep(5)