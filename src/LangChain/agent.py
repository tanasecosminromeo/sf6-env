import sys
import os
from datetime import datetime  # Add this import

# Immediate flush for all print statements
sys.stdout.reconfigure(line_buffering=True)  # Python 3.7+

print("==== SCRIPT STARTING ====")
print(f"Python version: {sys.version}")
print(f"Current directory: {os.getcwd()}")
print(f"Directory contents: {os.listdir('.')}")
print(f"Current time: {datetime.now()}")

# Add current directory to path to find local modules
sys.path.append(os.path.dirname(os.path.abspath(__file__)))
import sqs_client

if __name__ == "__main__":
    sqs_client.consume_sqs()