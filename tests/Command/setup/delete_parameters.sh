#!/usr/bin/env bash

# The parameters must be deleted in groups of maximum 10 so we need 2 requests

aws ssm delete-parameters \
    --profile envloader-test \
    --names \
        /envloader-test/empty \
        /envloader-test/empty2 \
        /envloader-test/numeric \
        /envloader-test/simple \
        /envloader-test/backslash \
        /envloader-test/no_substitution \
        /envloader-test/substitution \
        /envloader-test/double_quotes \
        /envloader-test/single_quotes \
        /envloader-test/backticks \
    --no-cli-pager

aws ssm delete-parameters \
    --profile envloader-test \
    --names \
        /envloader-test/special_chars \
        /envloader-test/not_secure \
        /envloader-test/overridden \
    --no-cli-pager
