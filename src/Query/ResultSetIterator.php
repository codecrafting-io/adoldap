<?php

namespace CodeCrafting\AdoLDAP\Query;

use Countable;
use SeekableIterator;
use OutOfBoundsException;
use InvalidArgumentException;
use CodeCrafting\AdoLDAP\Parsers\ParserInterface as Parser;

/**
 * Class ResultSetIterator.
 *
 * Class for retrieve and loop through thre result set
 */
class ResultSetIterator implements SeekableIterator, Countable
{
    const RESULTSET_STATUS_OPEN = 1;

    /**
     * Total number of elements of all pages
     *
     * @var int
     */
    private $elementCount = -1;

    /**
     * The result set entry parser
     *
     * @var Parser
     */
    private $parser;

    /**
     * ResultSet to paginate
     *
     * @var \VARIANT
     */
    private $rs;

    /**
     * Callbacks to be executed after a current operation
     *
     * @var \Closure[]
     */
    private $callbacks = [];

    /**
     * Constructor
     *
     * @param \VARIANT $resultSet
     * @param Parser $parser
     * @throws ResultSetIteratorExcpetion if resultSet is closed
     */
    public function __construct(\VARIANT $resultSet, Parser $parser)
    {
        if ($resultSet && $parser) {
            $this->rs = $resultSet;
            if ($this->isOpened()) {
                $this->parser = $parser;
            } else {
                throw new ResultSetIteratorException('ResultSet is not opened, unable to operate');
            }
        } else {
            throw new InvalidArgumentException('ResultSet must not be null');
        }
    }

    public function __destruct()
    {
        if ($this->isOpened()) {
            $this->rs->close();
        }
    }

    /**
     * Seeks to a given position. Starts from 0
     *
     * @param int $position
     * @throws OutOfBoundsException if positivon is invalid
     * @return void
     */
    public function seek($position)
    {
        if ($position >= 0) {
            if ($this->count() > 0) {
                $key = $this->key();
                if ($key === null || $key != 0) {
                    $this->rs->MoveFirst();
                }
                $this->rs->Move($position);
                if ($this->key() === null) {
                    throw new OutOfBoundsException("Invalid seek position ({$position})");
                }
            }
        } else {
            throw new OutOfBoundsException("Invalid seek position ({$position})");
        }
    }

    /**
     * Sets a afterFetch callback to handle data after a current operation
     *
     * @param \Closure $afterFetchCallback
     * @return self
     */
    public function afterFetch(\Closure $afterFetchCallback)
    {
        if ($afterFetchCallback) {
            $this->callbacks[] = $afterFetchCallback;
        } else {
            new InvalidArgumentException('afterFetch must not be null');
        }

        return $this;
    }

    /**
     * Returns the current element. Values can be altered by callbacks
     *
     * @return mixed|null
     */
    public function current()
    {
        if ($this->valid()) {
            $current = $this->parser->parseEntry($this->rs->fields);
            foreach ($this->callbacks as $callback) {
                $current = $callback($current);
            }

            return $current;
        }

        return null;
    }

    /**
     * Return the key of the current element. Starts from zero
     *
     * @return int|null
     */
    public function key()
    {
        $key = $this->rs->AbsolutePosition;
        return ($key >= 1) ? $key - 1 : null;
    }

    /**
     * Moves the current position to the next element.
     *
     * @return void
     */
    public function next()
    {
        if ($this->valid()) {
            $this->rs->MoveNext();
        }
    }

    /**
     * Moves the current position to the previous element.
     *
     * @return void
     */
    public function previous()
    {
        if ($this->valid()) {
            $this->rs->MovePrevious();
        }
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind()
    {
        $this->rs->MoveFirst();
    }

    /**
     * Fast foward to the last element
     *
     * @return void
     */
    public function fastFoward()
    {
        $this->rs->MoveLast();
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return ! ($this->rs->EOF || $this->rs->BOF);
    }

    /**
     * Count elements of an object.
     *
     * @return int
     */
    public function count()
    {
        if ($this->elementCount == -1) {
            if ($this->rs->RecordCount > 0) {
                $this->rs->MoveLast();
            }
            $this->elementCount = $this->rs->RecordCount;
        }

        return $this->elementCount;
    }

    /**
     * Retrieve and parse the elements from a result set
     *
     * @param integer $limit
     * @param integer $offset
     * @throws OutOfBoundsException if resultSet, limit or offset is invalid
     * @return array|null
     */
    public function getEntries(int $limit = 0, int $offset = 0)
    {
        if ($limit >= 0) {
            $size = 0;
            $elements = [];
            $this->seek($offset);
            while ($this->valid()) {
                $elements[] = $this->current();
                $size++;
                if ($limit != 0 && $size >= $limit) {
                    break;
                } else {
                    $this->next();
                }
            }

            return $elements;
        } else {
            throw new OutOfBoundsException("Invalid limit ({$limit})");
        }
    }


    /**
     * Check if whether or not the ADO ResultSet is opened
     *
     * @return bool
     */
    private function isOpened()
    {
        return $this->rs->State == self::RESULTSET_STATUS_OPEN;
    }
}
