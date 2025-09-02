import sys
import os
from datetime import datetime  # Add this import

# Print current time and date
print("Current time and date:", datetime.now())

# Add current directory to path to find local modules
sys.path.append(os.path.dirname(os.path.abspath(__file__)))
import sqs_client

if __name__ == "__main__":
    sqs_client.consume_sqs()