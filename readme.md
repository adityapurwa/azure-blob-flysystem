# Azure Blob Flysystem

> Wrapper for Azure Blob Storage for Flysystem.

## Important

Due to the nature that testing Azure storage requires an emulator,
or actually running it on Azure platform.
This adapter is not thoroughly tested yet, use at your own risk.
Any help on providing test is appreciated.

## Usages

Initializing adapter

```php
$adapter = new AzureBlobAdapter(
    'account_name',
    'account_key',
    'account_protocol' (default to https)
)
```