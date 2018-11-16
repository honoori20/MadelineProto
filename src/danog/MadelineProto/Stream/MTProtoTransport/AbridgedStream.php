<?php
/**
 * Abridged stream wrapper.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\MTProtoTransport;

use danog\MadelineProto\Stream\Async\BufferedStream;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoBufferInterface;

/**
 * Abridged stream wrapper.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class AbridgedStream implements BufferedStreamInterface, MTProtoBufferInterface
{
    use BufferedStream;

    private $stream;
    private $length = 0;

    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return \Generator
     */
    public function connectAsync(ConnectionContext $ctx): \Generator
    {
        $this->stream = yield $ctx->getStream();
        $buffer = yield $this->stream->getWriteBuffer(1);
        yield $buffer->bufferWrite(chr(239));
    }

    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     *
     * @return \Generator
     */
    public function getWriteBufferAsync(int $length): \Generator
    {
        if ($length < 127) {
            $message = chr($length);
        } else {
            $message = chr(127).substr(pack('V', $length), 0, 3);
        }
        $buffer = yield $this->stream->getWriteBuffer(strlen($message) + $length);
        yield $buffer->bufferWrite($message);

        return $buffer;
    }

    /**
     * Get read buffer asynchronously.
     *
     * @return Generator
     */
    public function getReadBufferAsync(): \Generator
    {
        $buffer = yield $this->stream->getReadBuffer();
        $length = ord(yield $buffer->bufferRead(1));
        if ($length >= 127) {
            $length = unpack('V', (yield $buffer->bufferRead(3))."\0")[1];
        }
        $this->length = $length;

        return $buffer;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public static function getName(): string
    {
        return __CLASS__;
    }
}