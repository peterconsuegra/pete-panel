#!/bin/sh
# get_ssh_pubkey.sh
# Usage: get_ssh_pubkey.sh /root/.ssh/id_rsa.pub
# Prints the public key to stdout if readable, otherwise returns non-zero and a message to stderr.

KEY_PATH="${1:-/var/www/.ssh/id_ed25519.pub}"

# Basic validation
if [ -z "$KEY_PATH" ]; then
  echo "No key path provided" >&2
  exit 2
fi

if [ ! -f "$KEY_PATH" ]; then
  echo "Key not found: $KEY_PATH" >&2
  exit 3
fi

if [ ! -r "$KEY_PATH" ]; then
  echo "Key exists but is not readable: $KEY_PATH" >&2
  exit 4
fi

# Safe output
# strip any trailing newlines, but keep the key intact
cat "$KEY_PATH"
exit 0