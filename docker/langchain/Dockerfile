# Use an official Python runtime as a parent image
FROM python:3.10-slim

# Set the working directory in the container
WORKDIR /app

# Copy the requirements file and install dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Install watchgod for hot reloading
RUN pip install --no-cache-dir watchgod

# Command to run the script when the container starts
# Run agent.py directly without using watchgod
CMD ["python", "agent.py"]

# Alternative commands:
# For using watchgod with a file
# CMD ["watchgod", "--args", "python /app/agent.py"]
# For using watchgod with a module (requires proper package structure)
# CMD ["python", "-m", "watchgod", "app.agent"] #python agent.py