# envloader
[![CircleCI Status](https://circleci.com/gh/curology/envloader.svg?style=shield)](https://circleci.com/gh/curology/envloader)
[![GitHub License](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)

Loads parameters from AWS SSM Parameter Store and uses them to generate a dotenv file.

## Installation
You can install envloader via composer:
```
composer require curology/envloader
```

## Usage

### Configuration
Before you can use the envloader, you must create a json config file. By default, envloader will look for a file named `envloader.json`.
```
touch envloader.json
```

Example `envloader.json`:
```json
{
    "awsProfile": "development",
    "awsRegion": "us-east-1",
    "environment": "development",
    "envPath": ".env.development",
    "envOverridePath": ".env.development.override",
    "workingDir": "/Users/curology/envloader",
    "parameterPrefix": "/envloader/development/",
    "parameterList": [
        "param1:1",
        "param2:5",
        "param3:2"
    ]
}
```

The config file should contain the following entries:

| Name        | Type   | Required | Default | Description |
| ----------- | -------| -------- | ------- | ----------- |
| awsProfile  | String | No       | default | The AWS profile from your `~/.aws/config` file that you wish to use. Alternatively, you can set the envloader-specific AWS environment variables `AWS_SSM_ACCESS_KEY_ID` and `AWS_SSM_SECRET_ACCESS_KEY`. If both the `awsProfile` and environment variables are unspecified, envloader will fall back to AWS's environment variables or default profile. The order of precedence is: <ol><li>`AWS_SSM_ACCESS_KEY_ID` and `AWS_SSM_SECRET_ACCESS_KEY`</li><li>`awsProfile`, if specified and not set to the default profile</li><li>The AWS environment variables `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY`</li><li>The `default` AWS profile from your `~/.aws/config`</li></ol> |
| awsRegion  | String | Yes       | - | The AWS region that you would like fetch parameters from. |
| environment | String | No | default | The name of the environment that the generated dotenv file will be associated with.
| envPath | String | No | .env | The relative path to the file where the generated dotenv file will be written. |
| envOverridePath | String | No | - | The relative path to the file containing the override values. Overrides will take precedence over parameters with the same name in AWS. You can also add values to the override file that do not appear in AWS and they will be added to the generated dotenv file. |
| workingDir | String | No | `getcwd()` | The working directory. `envPath` and `envOverridePath` are relative paths from the working directory.
| parameterPrefix | String | No | - | The prefix to the names of your parameters in AWS. |
| parameterList | Array | Yes | - | The list of parameter names you would like to fetch from AWS. The parameter names should be in the form `NAME:VERSION`. If you provided a `parameterPrefix`, it should be removed form the beginning of the names in the `parameterList`. |

### Run
To list the commands available:
```
vendor/bin/envloader list
```

If you do not specify a path to your config file in the commands below, envloader will use `envloader.json`.  

To generate your dotenv file:
```
vendor/bin/envloader generate PATH/TO/YOUR/CONFIG/FILE
```

To print the key, value pairs in your dotenv file in a formatted table:
```
vendor/bin/envloader show PATH/TO/YOUR/CONFIG/FILE --with-values
```
Remove the `--with-values` option to hide the parameter values.

## Testing

### Setup

#### Install Composer Dependencies
```
composer install
```

#### Create AWS Resources
To enable tests that require real data, set the `ENVLOADER_TEST_ENABLE_AWS` environment variable to `true`:
```
export ENVLOADER_TEST_ENABLE_AWS=true
```

If this variable is not set, the data-dependent tests will be skipped and you will not have to create any AWS resources.

To run all the tests, you will need real data in AWS SSM Parameter Store, and the required permissions to create the data.
The following instructions use the AWS CLI (`version 2.0.46` and above) - you can install it [here](https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-install.html).
You can alternatively create the parameters via the AWS console UI.

1. Create a user that will have permissions to edit and view the parameters
```
aws iam create-user --user-name envloader-test --no-cli-pager
```

2. Create an access key for the user. Keep track of the `AccessKeyId` and `SecretAccessKey` in the response.
```
aws iam create-access-key --user-name envloader-test
```

3. Add the `AccessKeyId` and `SecretAccessKey` from the response to your `~/.aws/credentials` file, along with your aws region:
```
[envloader-test]
region = `REGION`
aws_access_key_id = `AccessKeyId`
aws_secret_access_key = `SecretAccessKey`
```

4. Grant the user permissions to the SSM Parameters you will create.  
Open `tests/command/setup/iam_policy.json` and replace `REGION` and `ACCOUNT_ID` with the aws region and account ID where you will be creating the parameters.
```
aws iam put-user-policy \
    --user-name envloader-test \
    --policy-document file://tests/command/setup/iam_policy.json \
    --policy-name EnvLoaderTest \
    --no-cli-pager
```

5. Create the SSM Parameters.
```
./tests/Command/setup/create_parameters.sh
```

6. The command tests use `us-east-1` as the AWS region. You will have to change `tests/fixtures/config/test_envloader_config.json` to include the correct AWS region should you decide to use a different region.

### Run

Unit Tests
```
composer test-unit
```

Command Tests
```
composer test-command
```

All Tests
```
composer test
```

### Cleanup
If you want to destroy the AWS resources created for testing, run the following commands:

1. Delete the parameters.
```
./tests/Command/setup/delete_parameters.sh
```

2. Delete the user policy
```
aws iam delete-user-policy --user-name envloader-test --policy-name EnvLoaderTest --no-cli-pager
```

3. Delete the access key. Replace ``AccessKeyId`` with the value from your `~/.aws/config`
```
aws iam delete-access-key --user-name envloader-test --no-cli-pager --access-key-id `AccessKeyId`
```

3. Delete the user
```
aws iam delete-user --user-name envloader-test --no-cli-pager
```
