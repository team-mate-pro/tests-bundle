#!/usr/bin/sh

# Usage: ./run-if-modified.sh "command to run" /path/to/watch

CMD="$1"
WATCH_PATH="$2"

if [ -z "$CMD" ] || [ -z "$WATCH_PATH" ]; then
    echo "Usage: $0 \"command\" /path"
    exit 1
fi

# Create a timestamp file path based on the watched path
TIMESTAMP_FILE="/tmp/run-if-modified-$(echo "$WATCH_PATH" | sed 's/[\/]/-/g').timestamp"

# Check if timestamp file exists
if [ -f "$TIMESTAMP_FILE" ]; then
    # Find files newer than the timestamp file (BusyBox compatible)
    NEWER_FILE=$(find "$WATCH_PATH" -type f -newer "$TIMESTAMP_FILE" -print -quit 2>/dev/null)

    if [ -n "$NEWER_FILE" ]; then
        echo "Detected modification: $NEWER_FILE"
        eval "$CMD"
        RESULT=$?
        # Update timestamp only if command succeeded
        if [ $RESULT -eq 0 ]; then
            touch "$TIMESTAMP_FILE"
        fi
        exit $RESULT
    else
        echo "No modifications detected. Command not executed."
    fi
else
    # First run - create timestamp and run command
    echo "First run detected. Executing command..."
    eval "$CMD"
    RESULT=$?
    # Create timestamp file only if command succeeded
    if [ $RESULT -eq 0 ]; then
        touch "$TIMESTAMP_FILE"
    fi
    exit $RESULT
fi
