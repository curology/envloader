#!/usr/bin/env bash

echo Creating parameter: "/envloader-test/overridden"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/overridden \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value "old value" \
    --no-cli-pager

echo Creating parameter: "/envloader-test/empty"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/empty \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value "''" \
    --no-cli-pager

echo Creating parameter: "/envloader-test/empty2"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/empty2 \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value '""' \
    --no-cli-pager

echo Creating parameter: "/envloader-test/numeric"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/numeric \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value 5 \
    --no-cli-pager

echo Creating parameter: "/envloader-test/simple"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/simple \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value 'hello' \
    --no-cli-pager

echo Creating parameter: "/envloader-test/backslash"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/backslash \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value 'test\backslash' \
    --no-cli-pager

echo Creating parameter: "/envloader-test/no_substitution"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/no_substitution \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value '$SIMPLE' \
    --no-cli-pager

echo Creating parameter: "/envloader-test/substitution"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/substitution \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value '${SIMPLE}' \
    --no-cli-pager

echo Creating parameter: "/envloader-test/double_quotes"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/double_quotes \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value '"hello world"' \
    --no-cli-pager

echo Creating parameter: "/envloader-test/single_quotes"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/single_quotes \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value "'hello world'" \
    --no-cli-pager

echo Creating parameter: "/envloader-test/backticks"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/backticks \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value '`pwd`' \
    --no-cli-pager

echo Creating parameter: "/envloader-test/special_chars"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/special_chars \
    --type SecureString \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value 'skdlf2o3i~2u304&**@%&#%@^jsllvuwecn' \
    --no-cli-pager

echo Creating parameter: "/envloader-test/not_secure"
aws ssm put-parameter \
    --profile envloader-test \
    --name /envloader-test/not_secure \
    --type String \
    --description "test parameter for envloader" \
    --no-overwrite \
    --value 'plain text' \
    --no-cli-pager
