<?php

namespace AdityaPurwa\AzureBlobFlysystem\Adapter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

class AzureBlobAdapter implements AdapterInterface
{

    /**
     * @var BlobRestProxy
     */
    private $_proxy;

    public function __construct($accountName, $accountKey, $accountProtocol = 'https')
    {
        $this->_proxy = BlobRestProxy::createBlobService(
            "DefaultEndpointsProtocol={$accountProtocol};" .
            "AccountName={$accountName};" .
            "AccountKey={$accountKey}"
        );
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $container = pathinfo($path, PATHINFO_DIRNAME);
        $fname = pathinfo($path, PATHINFO_BASENAME);

        $data = $this->_proxy->createBlockBlob($container, $fname, $contents);
        return [
            'type' => 'file',
            'path' => $path,
            'contents' => $contents,
            'timestamp' => $data->getLastModified()
        ];

    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        $container = pathinfo($path, PATHINFO_DIRNAME);
        $fname = pathinfo($path, PATHINFO_BASENAME);

        $data = $this->_proxy->createBlockBlob($container, $fname, $resource);
        return [
            'type' => 'file',
            'path' => $path,
            'stream' => $resource,
            'timestamp' => $data->getLastModified()
        ];

    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return false;
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        return false;
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {

        $container = pathinfo($path, PATHINFO_DIRNAME);
        $fname = pathinfo($path, PATHINFO_BASENAME);

        $newContainer = pathinfo($newpath, PATHINFO_DIRNAME);
        $newFname = pathinfo($newpath, PATHINFO_BASENAME);

        $blobData = $this->_proxy->getBlob($container, $fname);

        $this->_proxy->createBlockBlob($newContainer, $newFname, $blobData->getContentStream());
        $this->_proxy->deleteBlob($container, $fname);

        return true;

    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {

        $container = pathinfo($path, PATHINFO_DIRNAME);
        $fname = pathinfo($path, PATHINFO_BASENAME);

        $newContainer = pathinfo($newpath, PATHINFO_DIRNAME);
        $newFname = pathinfo($newpath, PATHINFO_BASENAME);

        $blobData = $this->_proxy->getBlob($container, $fname);

        $this->_proxy->createBlockBlob($newContainer, $newFname, $blobData->getContentStream());

        return true;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        $container = pathinfo($path, PATHINFO_DIRNAME);
        $fname = pathinfo($path, PATHINFO_BASENAME);
        $this->_proxy->deleteBlob($container, $fname);

        return true;
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        $this->_proxy->deleteContainer($dirname);
        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        $this->_proxy->createContainer($dirname);
        return [
            'type' => 'directory',
            'path' => $dirname,
        ];

    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        return false;
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        $container = pathinfo($path, PATHINFO_DIRNAME);
        $fname = pathinfo($path, PATHINFO_BASENAME);

        try {
            return $this->_proxy->getBlob($container, $fname);
        } catch (ServiceException $ex) {
            return false;
        }
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        $container = pathinfo($path, PATHINFO_DIRNAME);
        $fname = pathinfo($path, PATHINFO_BASENAME);
        $data = $this->_proxy->getBlob($container, $fname);
        $contents = stream_get_contents($data->getContentStream());
        return [
            'type' => 'file',
            'path' => $path,
            'contents' => $contents,
            'timestamp' => $data->getProperties()->getLastModified()
        ];

    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        $container = pathinfo($path, PATHINFO_DIRNAME);
        $fname = pathinfo($path, PATHINFO_BASENAME);
        $data = $this->_proxy->getBlob($container, $fname);
        return [
            'type' => 'file',
            'path' => $path,
            'stream' => $data->getContentStream(),
            'timestamp' => $data->getProperties()->getLastModified()
        ];
    }

    /**
     * List contents of a directory. Because the nature of continuation
     * token from Azure, don't use this method.
     *
     * @param string $directory
     * @param bool $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        if ($directory === '') {
            return $this->_proxy->listContainers()->getContainers();
        }
        return $this->_proxy->listBlobs($directory)->getBlobs();
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        $container = pathinfo($path, PATHINFO_DIRNAME);
        $fname = pathinfo($path, PATHINFO_BASENAME);

        $data = $this->_proxy->getBlobMetadata($container, $fname);
        return $data->getMetadata();
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return false;
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        return false;
    }

    /**
     * Get the last modified time of a file as a timestamp.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        $container = pathinfo($path, PATHINFO_DIRNAME);
        $fname = pathinfo($path, PATHINFO_BASENAME);

        $data = $this->_proxy->getBlobMetadata($container, $fname);
        return [
            'timestamp' => $data->getLastModified()
        ];

    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        return false;
    }
}