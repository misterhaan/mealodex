<?php
require_once __DIR__ . '/row.php';

/**
 * Represents a row looked up from the ingredient table.
 */
class IngredientRow extends Row {
	private const Tolerance = .05;

	private static $fractions = [
		['value' => 1/4, 'display' => '¼'],
		['value' => 1/3, 'display' => '⅓'],
		['value' => 1/2, 'display' => '½'],
		['value' => 2/3, 'display' => '⅔'],
		['value' => 3/4, 'display' => '¾'],
		['value' => 1, 'display' => '']
	];

	public $sort;
	public $item;
	public $amount;
	public $unit;
	public $prep;

	/**
	 * Initialize linked rows.
	 */
	public function __construct() {
		$this->item = new Row();
		$this->unit = new Row();
		$this->prep = new Row();
	}

	/**
	 * Duplicate this ingredient row so that the next row can be loaded from the
	 * database without overwriting this one because the php clone operator
	 * doesn't work here.  Also sets the display amount.
	 * @return IngredientRow Duplicate of the current IngredientRow object
	 */
	public function dupe() {
		$d = parent::dupe();
		$d->displayAmount = $this->getDisplayAmount();
		return $d;
	}

	/**
	 * Use fraction characters to display the fractional part of the amount.
	 * @return string Amount property formatted for display
	 */
	private function getDisplayAmount(): string {
		$amount = $this->amount;
		$whole = floor($this->amount);
		$fraction = $this->amount - $whole;

		if(!$whole)
			$whole = '';

		if(!$fraction)
			return $whole;

		$prevFrac = ['value' => 0, 'display' => ''];
		foreach(self::$fractions as $frac) {
			if($fraction == $frac['value'])
				return $whole . $frac['display'];
			if($fraction < $frac['value'])
				if($frac['value'] - $fraction < $fraction - $prevFrac['value'])
					if($frac['display'])
						return $whole . $frac['display'];
					else
						return $whole + 1;
				else
					return $whole . $prevFrac['display'];
			$prevFrac = $frac;
		}

		return round($this->amount, 3) . '';
	}
}
