#!/bin/bash
# This script will update a single key within a set of vault secrets.
# Usage: ./update-vault.sh secret-store key value

echo $BASH_VERSION

set -o errexit -euo pipefail -o noclobber -o nounset

####################################################################################
# Prerequisites - You'll need the following installed
#    AWS CLI - http://docs.aws.amazon.com/cli/latest/userguide/installing.html
#    Cloud Foundry CLI - https://docs.cloudfoundry.org/cf-cli/install-go-cli.html
#    Vault CLI - https://www.vaultproject.io/docs/install/index.html
####################################################################################
! getopt --test > /dev/null
if [[ ${PIPESTATUS[0]} -ne 4 ]]; then
    echo "################################################################################################"
    echo >&2 'Error: `getopt --test` failed in this environment.'
    echo "################################################################################################"

    exit 1
fi

command -v vault >/dev/null 2>&1 || {
    echo "################################################################################################"
    echo >&2 "Please install Vault CLI - https://www.vaultproject.io/docs/install/index.html"
    echo "################################################################################################"
    exit 1
}

####################################################################################
# Set required parameters
#    SECRET_STORE (required) - the secret store to update
#    KEY (required) - the key to update
#    VALUE (required) - the value to update
#    VAULT_ADDR - the vault service endpoint
#    VAULT_UNSEAL (required) - the key used to unseal the vault
#    VAULT_TOKEN (required) - the master token to unseal the vaule
####################################################################################
OPTIONS=v:u:t:
LONGOPTS=vault:,unseal:,token:

# -use ! and PIPESTATUS to get exit code with errexit set
# -temporarily store output to be able to check for errors
# -activate quoting/enhanced mode (e.g. by writing out “--options”)
# -pass arguments only via   -- "$@"   to separate them correctly
! PARSED=$(getopt --options=$OPTIONS --longoptions=$LONGOPTS --name "$0" -- "$@")
if [[ ${PIPESTATUS[0]} -ne 0 ]]; then
    # e.g. return value is 1
    #  then getopt has complained about wrong arguments to stdout
    exit 2
fi
# read getopt’s output this way to handle the quoting right:
eval set -- "$PARSED"

# Defaults
VAULT_ADDR=${VAULT_ADDR:="https://vault.primary-authority.services:8200"}
VAULT_UNSEAL=${VAULT_UNSEAL:-}
VAULT_TOKEN=${VAULT_TOKEN:-}

while true; do
    case "$1" in
        -v|--vault)
            VAULT_ADDR="$2"
            shift 2
            ;;
        -u|--unseal)
            VAULT_UNSEAL="$2"
            shift 2
            ;;
        -t|--token)
            VAULT_TOKEN="$2"
            shift 2
            ;;
        --)
            shift
            break
            ;;
        *)
            echo "Programming error"
            exit 3
            ;;
    esac
done

## Ensure an environment has been passed
if [[ $# -ne 3 ]]; then
    printf "Please specify the secret store and the key to update...\n"
    exit 4
fi
SECRET=$1
KEY=$2
VALUE=$3

####################################################################################
# Allow manual input of missing parameters
#    VAULT_TOKEN (required) - the vault token to unseal the vault
#    VAULT_UNSEAL (required) - the key used to unseal the vault
####################################################################################
if [[ -z "${VAULT_UNSEAL}" ]]; then
    echo -n "Enter your Vault unseal key (will be hidden): "
    read -s VAULT_UNSEAL
fi
if [[ -z "${VAULT_TOKEN}" ]]; then
    echo -n "Enter your Vault master token (will be hidden): "
    read -s VAULT_TOKEN
fi

####################################################################################
# Copy vault secrets to another store
####################################################################################
printf "Extracting Vault secrets...\n"

export VAULT_ADDR
export VAULT_TOKEN

vault operator seal -tls-skip-verify
vault operator unseal -tls-skip-verify $VAULT_UNSEAL

## Set the environment variables by generating an .env file
printf "Extracting values from vault keystore: '$SECRET'...\n"
rm -f .env
VAULT_VARS=($(vault kv get -tls-skip-verify secret/par/env/$SECRET | awk 'NR > 3 {print $1}'))

## Be sure to add any new keys not in vault
if (printf '%s\n' "${VAULT_VARS[@]}" | grep -xq $KEY); then
    printf "Updating an existing key: '$KEY'...\n"
else
    printf "Adding a new key: '$KEY'...\n"
    VAULT_VARS+=($KEY)
fi

## Generate the vault variables into a writable string
VAULT_STRING=''
for VAR_NAME in "${VAULT_VARS[@]}"
do
    if [[ $VAR_NAME == $KEY ]]; then
      VAR="$VAR_NAME='$VALUE' "
    else
      VAR="$VAR_NAME='$(vault kv get --field=$VAR_NAME -tls-skip-verify secret/par/env/$SECRET)' "
    fi
    VAULT_STRING+="$VAR"
done

vault write -tls-skip-verify secret/par/env/$SECRET $VAULT_STRING

vault operator seal -tls-skip-verify
