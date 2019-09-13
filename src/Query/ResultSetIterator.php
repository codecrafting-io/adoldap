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
     * Only returns the name for container values
     *
     * @var bool
     */
    private $containerNameOnly = true;

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
     * Constructor
     *
     * @param \VARIANT $resultSet
     * @param Parser $parser
     * @throws ResultSetPaginatorExcpetion if resultSet is closed
     */
    public function __construct(\VARIANT $resultSet, Parser $parser)
    {
        if ($resultSet && $parser) {
            $this->rs = $resultSet;
            if ($this->isOpened()) {
                $this->parser = $parser;
            } else {
                throw new ResultSetPaginatorException('ResultSet is not opened, unable to operate');
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
     * Get only returns the name for container values
     *
     * @return  bool
     */
    public function getContainerNameOnly()
    {
        return $this->containerNameOnly;
    }

    /**
     * Set only returns the name for container values
     *
     * @param  bool  $containerNameOnly  Only returns the name for container values
     *
     * @return  self
     */
    public function setContainerNameOnly(bool $containerNameOnly)
    {
        $this->containerNameOnly = $containerNameOnly;

        return $this;
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
            $key = $this->key();
            if ($key === null || $key != 0) {
                $this->rs->MoveFirst();
            }
            $this->rs->Move($position);
            if ($this->key() === null) {
                throw new OutOfBoundsException("Invalid seek position ({$position})");
            }
        } else {
            throw new OutOfBoundsException("Invalid seek position ({$position})");
        }
    }

    /**
     * Returns the current element
     *
     * @return array|null
     */
    public function current()
    {
        if ($this->valid()) {
            $current = [];
            foreach ($this->rs->fields as $key => $field) {
                if ($field->name == 'distinguishedName') {
                    $current[$field->name] = $this->parser->parse($field, false);
                } else {
                    $current[$field->name] = $this->parser->parse($field, $this->containerNameOnly);
                }
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
            $this->rs->MoveLast();
            $this->elementCount = $this->rs->RecordCount;
        }

        return $this->elementCount;
    }

    /**
     * Retrieve and parse the elements from a result set
     *
     * @param integer $offset
     * @param integer $limit
     * @throws OutOfBoundsException if resultSet, limit or offset is invalid
     * @return array|null
     */
    public function getElements(int $offset = 0, int $limit = 0)
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
