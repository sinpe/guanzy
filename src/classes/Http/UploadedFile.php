<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sinpe\Framework\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * UploadedFile class
 */
class UploadedFile implements UploadedFileInterface
{
    /**
     * The client-provided full path to the file
     *
     * @var string
     */
    protected $file;

    /**
     * The client-provided file name.
     *
     * @var string|null
     */
    protected $name;

    /**
     * The client-provided media type of the file.
     *
     * @var string|null
     */
    protected $type;

    /**
     * @var int|null
     */
    protected $size;

    /**
     * A valid PHP UPLOAD_ERR_xxx code for the file upload.
     *
     * @var int
     */
    protected $error = UPLOAD_ERR_OK;

    /**
     * @var StreamInterface|null
     */
    protected $stream;

    /**
     * Indicates if the uploaded file has already been moved.
     *
     * @var bool
     */
    protected $moved = false;

    /**
     * @param string|StreamInterface $fileNameOrStream The full path to the uploaded file provided by the client,
     *                                                 or a StreamInterface instance.
     * @param string|null            $name             The file name.
     * @param string|null            $type             The file media type.
     * @param int|null               $size             The file size in bytes.
     * @param int                    $error            The UPLOAD_ERR_XXX code representing the status of the upload.
     */
    public function __construct(
        $fileNameOrStream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ) {
        if ($fileNameOrStream instanceof StreamInterface) {
            $file = $fileNameOrStream->getMetadata('uri');
            if (!is_string($file)) {
                throw new \InvalidArgumentException('No URI associated with the stream.');
            }
            $this->file = $file;
            $this->stream = $fileNameOrStream;
        } elseif (is_string($fileNameOrStream)) {
            $this->file = $fileNameOrStream;
        } else {
            throw new \InvalidArgumentException(
                'Please provide a string (full path to the uploaded file) or an instance of StreamInterface.'
            );
        }
        $this->name = $clientFilename;
        $this->type = $clientMediaType;
        $this->size = $size;
        $this->error = $error;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream()
    {
        $this->validateActive();

        if (!$this->stream) {
            $this->stream = new FileStream($this->file, 'r+');
        }

        return $this->stream;
    }

    /**
     * @throws \RuntimeException if is moved or not ok
     */
    private function validateActive(): void
    {
        if (UPLOAD_ERR_OK !== $this->error) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function moveTo($targetPath): void
    {
        $this->validateActive();

        if (!is_string($targetPath) || '' === $targetPath) {
            throw new \InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
        }

        if (null !== $this->file) {

            $targetIsStream = strpos($targetPath, '://') > 0;

            if (!$targetIsStream && !is_writable(dirname($targetPath))) {
                throw new \InvalidArgumentException('Upload target path is not writable');
            }

            if ($targetIsStream) {
                if (!copy($this->file, $targetPath)) {
                    throw new \RuntimeException(sprintf('Error moving uploaded file %s to %s', $this->name, $targetPath));
                }

                if (!unlink($this->file)) {
                    throw new \RuntimeException(sprintf('Error removing uploaded file %s', $this->name));
                }
            } else {
                $this->moved = php_sapi_name() == 'cli'
                    ? rename($this->file, $targetPath)
                    : move_uploaded_file($this->file, $targetPath);
            }

        } else {

            $stream = $this->getStream();

            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            // Copy the contents of a stream into another stream until end-of-file.
            $dest = new FileStream($targetPath, 'w');

            while (!$stream->eof()) {
                if (!$dest->write($stream->read(1048576))) {
                    break;
                }
            }

            $this->moved = true;
        }

        if (false === $this->moved) {
            throw new \RuntimeException(\sprintf('Uploaded file could not be moved to %s', $targetPath));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType(): ?string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return $this->size;
    }
    
}
