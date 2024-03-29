<?php

/**
 * Represents a generic row looked up from the database.
 */
class Row {
	/**
	 * Duplicate this row so that the next row can be loaded from the database
	 * without overwriting this one because the php clone operator doesn't work
	 * here.
	 * @return static Duplicate of the current Row object
	 */
	public function dupe(): static {
		$d = new static();
		foreach ($this as $name => $value)
			$d->$name = is_a($value, 'Row') ? $value->dupe() : $value;
		return $d;
	}
}
