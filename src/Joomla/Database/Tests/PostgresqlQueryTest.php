<?php
/**
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Database\Tests;

use Joomla\Test\TestHelper;

/**
 * Test class for JDatabasePostgresqlQuery.
 *
 * @since  1.0
 */
class PostgresqlQueryTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var  JDatabase  A mock of the JDatabase object for testing purposes.
	 */
	protected $dbo;

	/**
	 * Data for the testNullDate test.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function dataTestNullDate()
	{
		return array(
			// Quoted, expected
			array(true, "'_1970-01-01 00:00:00_'"),
			array(false, "1970-01-01 00:00:00"),
		);
	}

	/**
	 * Data for the testNullDate test.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function dataTestQuote()
	{
		return array(
			// Text, escaped, expected
			array('text', false, '\'text\''),
		);
	}

	/**
	 * Data for the testJoin test.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function dataTestJoin()
	{
		return array(
			// $type, $conditions
			array('', 		'b ON b.id = a.id'),
			array('INNER',	'b ON b.id = a.id'),
			array('OUTER',	'b ON b.id = a.id'),
			array('LEFT',	'b ON b.id = a.id'),
			array('RIGHT',	'b ON b.id = a.id'),
		);
	}

	/**
	 * A mock callback for the database escape method.
	 *
	 * We use this method to ensure that JDatabaseQuery's escape method uses the
	 * the database object's escape method.
	 *
	 * @param   string  $text  The input text.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function mockEscape($text)
	{
		return "_{$text}_";
	}

	/**
	 * A mock callback for the database quoteName method.
	 *
	 * We use this method to ensure that JDatabaseQuery's quoteName method uses the
	 * the database object's quoteName method.
	 *
	 * @param   string  $text  The input text.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function mockQuoteName($text)
	{
		return '"' . $text . '"';
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->dbo = Mock\Driver::create($this, '1970-01-01 00:00:00', 'Y-m-d H:i:s');

		// Mock the escape method to ensure the API is calling the DBO's escape method.
		TestHelper::assignMockCallbacks(
			$this->dbo,
			$this,
			array('escape' => array($this, 'mockEscape'))
		);
	}

	/**
	 * Test for the \Joomla\Database\Postgresql\PostgresqlQuery::__string method for a 'select' case.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function test__toStringSelect()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$q->select('a.id')
			->from('a')
			->innerJoin('b ON b.id = a.id')
			->where('b.id = 1')
			->group('a.id')
				->having('COUNT(a.id) > 3')
			->order('a.id');

		$this->assertThat(
			(string) $q,
			$this->equalTo(
				PHP_EOL . "SELECT a.id" .
				PHP_EOL . "FROM a" .
				PHP_EOL . "INNER JOIN b ON b.id = a.id" .
				PHP_EOL . "WHERE b.id = 1" .
				PHP_EOL . "GROUP BY a.id" .
				PHP_EOL . "HAVING COUNT(a.id) > 3" .
				PHP_EOL . "ORDER BY a.id"
			),
			'Tests for correct rendering.'
		);
	}

	/**
	 * Test for the JDatabaseQuery::__string method for a 'update' case.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function test__toStringUpdate()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$q->update('#__foo AS a')
			->join('INNER', 'b ON b.id = a.id')
			->set('a.id = 2')
			->where('b.id = 1');

		$this->assertThat(
			(string) $q,
			$this->equalTo(
				PHP_EOL . "UPDATE #__foo AS a" .
				PHP_EOL . "SET a.id = 2" .
				PHP_EOL . "FROM b" .
				PHP_EOL . "WHERE b.id = 1 AND b.id = a.id"
			),
			'Tests for correct rendering.'
		);
	}

	/**
	 * Test for year extraction from date.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function test__toStringYear()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$q->select($q->year($q->quoteName('col')))->from('table');

		$this->assertThat(
					(string) $q,
					$this->equalTo(PHP_EOL . "SELECT EXTRACT (YEAR FROM \"col\")" . PHP_EOL . "FROM table")
		);
	}

	/**
	 * Test for month extraction from date.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function test__toStringMonth()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$q->select($q->month($q->quoteName('col')))->from('table');

		$this->assertThat(
					(string) $q,
					$this->equalTo(PHP_EOL . "SELECT EXTRACT (MONTH FROM \"col\")" . PHP_EOL . "FROM table")
		);
	}

	/**
	 * Test for day extraction from date.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function test__toStringDay()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$q->select($q->day($q->quoteName('col')))->from('table');

		$this->assertThat(
					(string) $q,
					$this->equalTo(PHP_EOL . "SELECT EXTRACT (DAY FROM \"col\")" . PHP_EOL . "FROM table")
		);
	}

	/**
	 * Test for hour extraction from date.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function test__toStringHour()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$q->select($q->hour($q->quoteName('col')))->from('table');

		$this->assertThat(
					(string) $q,
					$this->equalTo(PHP_EOL . "SELECT EXTRACT (HOUR FROM \"col\")" . PHP_EOL . "FROM table")
		);
	}

	/**
	 * Test for minute extraction from date.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function test__toStringMinute()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$q->select($q->minute($q->quoteName('col')))->from('table');

		$this->assertThat(
					(string) $q,
					$this->equalTo(PHP_EOL . "SELECT EXTRACT (MINUTE FROM \"col\")" . PHP_EOL . "FROM table")
		);
	}

	/**
	 * Test for seconds extraction from date.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function test__toStringSecond()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$q->select($q->second($q->quoteName('col')))->from('table');

		$this->assertThat(
					(string) $q,
					$this->equalTo(PHP_EOL . "SELECT EXTRACT (SECOND FROM \"col\")" . PHP_EOL . "FROM table")
		);
	}

	/**
	 * Test for INSERT INTO clause with subquery.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function test__toStringInsert_subquery()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$subq = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$subq->select('col2')->where('a=1');

		$q->insert('table')->columns('col')->values($subq);

		$this->assertThat(
					(string) $q,
					$this->equalTo(PHP_EOL . "INSERT INTO table" . PHP_EOL . "(col)" . PHP_EOL . "(" . PHP_EOL . "SELECT col2" . PHP_EOL . "WHERE a=1)")
		);

		$q->clear();
		$q->insert('table')->columns('col')->values('3');
		$this->assertThat(
					(string) $q,
					$this->equalTo(PHP_EOL . "INSERT INTO table" . PHP_EOL . "(col) VALUES " . PHP_EOL . "(3)")
		);
	}

	/**
	 * Test for the castAsChar method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testCastAsChar()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->castAsChar('123'),
			$this->equalTo('123::text'),
			'The default castAsChar behaviour is quote the input.'
		);
	}

	/**
	 * Test for the charLength method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testCharLength()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->charLength('a.title'),
			$this->equalTo('CHAR_LENGTH(a.title)')
		);
	}

	/**
	 * Test chaining.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testChaining()
	{
		$q = $this->dbo->getQuery(true)->select('foo');

		$this->assertThat(
			$q,
			$this->isInstanceOf('\Joomla\Database\DatabaseQuery')
		);
	}

	/**
	 * Test for the clear method (clearing all types and clauses).
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testClear_all()
	{
		$properties = array(
			'select',
			'delete',
			'update',
			'insert',
			'from',
			'join',
			'set',
			'where',
			'group',
			'having',
			'order',
			'columns',
			'values',
			'forShare',
			'forUpdate',
			'limit',
			'noWait',
			'offset',
			'returning',
		);

		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		// First pass - set the values.
		foreach ($properties as $property)
		{
			TestHelper::setValue($q, $property, $property);
		}

		// Clear the whole query.
		$q->clear();

		// Check that all properties have been cleared
		foreach ($properties as $property)
		{
			$this->assertThat(
				$q->$property,
				$this->equalTo(null)
			);
		}

		// And check that the type has been cleared.
		$this->assertThat(
			$q->type,
			$this->equalTo(null)
		);
	}

	/**
	 * Test for the clear method (clearing each clause).
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testClear_clause()
	{
		$clauses = array(
			'from',
			'join',
			'set',
			'where',
			'group',
			'having',
			'order',
			'columns',
			'values',
			'forShare',
			'forUpdate',
			'limit',
			'noWait',
			'offset',
			'returning',
		);

		// Test each clause.
		foreach ($clauses as $clause)
		{
			$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

			// Set the clauses
			foreach ($clauses as $clause2)
			{
				TestHelper::setValue($q, $clause2, $clause2);
			}

			// Clear the clause.
			$q->clear($clause);

			// Check that clause was cleared.
			$this->assertThat(
				$q->$clause,
				$this->equalTo(null)
			);

			// Check the state of the other clauses.
			foreach ($clauses as $clause2)
			{
				if ($clause != $clause2)
				{
					$this->assertThat(
						$q->$clause2,
						$this->equalTo($clause2),
						"Clearing '$clause' resulted in '$clause2' having a value of " . $q->$clause2 . '.'
					);
				}
			}
		}
	}

	/**
	 * Test for the clear method (clearing each query type).
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testClear_type()
	{
		$types = array(
			'select',
			'delete',
			'update',
			'insert',
			'forShare',
			'forUpdate',
			'limit',
			'noWait',
			'offset',
			'returning',
		);

		$clauses = array(
			'from',
			'join',
			'set',
			'where',
			'group',
			'having',
			'order',
			'columns',
			'values',
		);

		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		// Set the clauses.
		foreach ($clauses as $clause)
		{
			TestHelper::setValue($q, $clause, $clause);
		}

		// Check that all properties have been cleared
		foreach ($types as $type)
		{
			// Set the type.
			TestHelper::setValue($q, $type, $type);

			// Clear the type.
			$q->clear($type);

			// Check the type has been cleared.
			$this->assertThat(
				$q->type,
				$this->equalTo(null)
			);

			$this->assertThat(
				$q->$type,
				$this->equalTo(null)
			);

			// Now check the claues have not been affected.
			foreach ($clauses as $clause)
			{
				$this->assertThat(
					$q->$clause,
					$this->equalTo($clause)
				);
			}
		}
	}

	/**
	 * Test for "concatenate" words.
	 *
	 * @return  void
	 */
	public function testConcatenate()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->concatenate(array('foo', 'bar')),
			$this->equalTo('foo || bar'),
			'Tests without separator.'
		);

		$this->assertThat(
			$q->concatenate(array('foo', 'bar'), ' and '),
			$this->equalTo("foo || '_ and _' || bar"),
			'Tests without separator.'
		);
	}

	/**
	 * Test for FROM clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testFrom()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->from('#__foo'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->from),
			$this->equalTo('FROM #__foo'),
			'Tests rendered value.'
		);

		// Add another column.
		$q->from('#__bar');

		$this->assertThat(
			trim($q->from),
			$this->equalTo('FROM #__foo,#__bar'),
			'Tests rendered value after second use.'
		);
	}

	/**
	 * Test for GROUP clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testGroup()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->group('foo'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->group),
			$this->equalTo('GROUP BY foo'),
			'Tests rendered value.'
		);

		// Add another column.
		$q->group('bar');

		$this->assertThat(
			trim($q->group),
			$this->equalTo('GROUP BY foo,bar'),
			'Tests rendered value after second use.'
		);
	}

	/**
	 * Test for HAVING clause using a simple condition and with glue for second one.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testHaving()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->having('COUNT(foo) > 1'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->having),
			$this->equalTo('HAVING COUNT(foo) > 1'),
			'Tests rendered value.'
		);

		// Add another column.
		$q->having('COUNT(bar) > 2');

		$this->assertThat(
			trim($q->having),
			$this->equalTo('HAVING COUNT(foo) > 1 AND COUNT(bar) > 2'),
			'Tests rendered value after second use.'
		);

		// Reset the field to test the glue.
		TestHelper::setValue($q, 'having', null);
		$q->having('COUNT(foo) > 1', 'OR');
		$q->having('COUNT(bar) > 2');

		$this->assertThat(
			trim($q->having),
			$this->equalTo('HAVING COUNT(foo) > 1 OR COUNT(bar) > 2'),
			'Tests rendered value with OR glue.'
		);
	}

	/**
	 * Test for INNER JOIN clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testInnerJoin()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$q2 = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$condition = 'foo ON foo.id = bar.id';

		$this->assertThat(
			$q->innerJoin($condition),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$q2->join('INNER', $condition);

		$this->assertThat(
			$q->join,
			$this->equalTo($q2->join),
			'Tests that innerJoin is an alias for join.'
		);
	}

	/**
	 * Test for JOIN clause using dataprovider to test all types of join.
	 *
	 * @param   string  $type        Type of JOIN, could be INNER, OUTER, LEFT, RIGHT
	 * @param   string  $conditions  Join condition
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @dataProvider  dataTestJoin
	 */
	public function testJoin($type, $conditions)
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->join('INNER', 'foo ON foo.id = bar.id'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->join[0]),
			$this->equalTo('INNER JOIN foo ON foo.id = bar.id'),
			'Tests that first join renders correctly.'
		);

		$q->join('OUTER', 'goo ON goo.id = car.id');

		$this->assertThat(
			trim($q->join[1]),
			$this->equalTo('OUTER JOIN goo ON goo.id = car.id'),
			'Tests that second join renders correctly.'
		);
	}

	/**
	 * Test for LEFT JOIN clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testLeftJoin()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$q2 = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$condition = 'foo ON foo.id = bar.id';

		$this->assertThat(
			$q->leftJoin($condition),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$q2->join('LEFT', $condition);

		$this->assertThat(
			$q->join,
			$this->equalTo($q2->join),
			'Tests that innerJoin is an alias for join.'
		);
	}

	/**
	 * Tests the quoteName method.
	 *
	 * @param   boolean  $quoted    The value of the quoted argument.
	 * @param   string   $expected  The expected result.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @dataProvider  dataTestNullDate
	 */
	public function testNullDate($quoted, $expected)
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->nullDate($quoted),
			$this->equalTo($expected),
			'The nullDate method should be a proxy for the JDatabase::getNullDate method.'
		);
	}

	/**
	 * Test for ORDER clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testOrder()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->order('column'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->order),
			$this->equalTo('ORDER BY column'),
			'Tests rendered value.'
		);

		$q->order('col2');
		$this->assertThat(
			trim($q->order),
			$this->equalTo('ORDER BY column,col2'),
			'Tests rendered value.'
		);
	}

	/**
	 * Test for OUTER JOIN clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testOuterJoin()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$q2 = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$condition = 'foo ON foo.id = bar.id';

		$this->assertThat(
			$q->outerJoin($condition),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$q2->join('OUTER', $condition);

		$this->assertThat(
			$q->join,
			$this->equalTo($q2->join),
			'Tests that innerJoin is an alias for join.'
		);
	}

	/**
	 * Tests the quoteName method.
	 *
	 * @param   boolean  $text      The value to be quoted.
	 * @param   boolean  $escape    True to escape the string, false to leave it unchanged.
	 * @param   string   $expected  The expected result.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @dataProvider  dataTestQuote
	 */
	public function testQuote($text, $escape, $expected)
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->quoteName("test"),
			$this->equalTo('"test"'),
			'The quoteName method should be a proxy for the JDatabase::escape method.'
		);
	}

	/**
	 * Tests the quoteName method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testQuoteName()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->quoteName('test'),
			$this->equalTo('"test"'),
			'The quoteName method should be a proxy for the JDatabase::escape method.'
		);
	}

	/**
	 * Test for RIGHT JOIN clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testRightJoin()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$q2 = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$condition = 'foo ON foo.id = bar.id';

		$this->assertThat(
			$q->rightJoin($condition),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$q2->join('RIGHT', $condition);

		$this->assertThat(
			$q->join,
			$this->equalTo($q2->join),
			'Tests that innerJoin is an alias for join.'
		);
	}

	/**
	 * Test for SELECT clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testSelect()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->select('foo'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			$q->type,
			$this->equalTo('select'),
			'Tests the type property is set correctly.'
		);

		$this->assertThat(
			trim($q->select),
			$this->equalTo('SELECT foo'),
			'Tests the select element is set correctly.'
		);

		$q->select('bar');

		$this->assertThat(
			trim($q->select),
			$this->equalTo('SELECT foo,bar'),
			'Tests the second use appends correctly.'
		);

		$q->select(
			array(
				'goo', 'car'
			)
		);

		$this->assertThat(
			trim($q->select),
			$this->equalTo('SELECT foo,bar,goo,car'),
			'Tests the second use appends correctly.'
		);
	}

	/**
	 * Test for WHERE clause using a simple condition and with glue for second one.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testWhere()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);
		$this->assertThat(
			$q->where('foo = 1'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->where),
			$this->equalTo('WHERE foo = 1'),
			'Tests rendered value.'
		);

		// Add another column.
		$q->where(
			array(
				'bar = 2',
				'goo = 3',
			)
		);

		$this->assertThat(
			trim($q->where),
			$this->equalTo('WHERE foo = 1 AND bar = 2 AND goo = 3'),
			'Tests rendered value after second use and array input.'
		);

		// Clear the where
		TestHelper::setValue($q, 'where', null);
		$q->where(
			array(
				'bar = 2',
				'goo = 3',
			),
			'OR'
		);

		$this->assertThat(
			trim($q->where),
			$this->equalTo('WHERE bar = 2 OR goo = 3'),
			'Tests rendered value with glue.'
		);
	}

	/**
	 * Tests the \Joomla\Database\Postgresql\PostgresqlQuery::escape method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testEscape()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->escape('foo'),
			$this->equalTo('_foo_')
		);
	}

	/**
	 * Test for FOR UPDATE clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testForUpdate ()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->forUpdate('#__foo'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->forUpdate),
			$this->equalTo('FOR UPDATE OF #__foo'),
			'Tests rendered value.'
		);

		$q->forUpdate('#__bar');
		$this->assertThat(
			trim($q->forUpdate),
			$this->equalTo('FOR UPDATE OF #__foo, #__bar'),
			'Tests rendered value.'
		);

		// Testing glue
		TestHelper::setValue($q, 'forUpdate', null);
		$q->forUpdate('#__foo', ';');
		$q->forUpdate('#__bar');
		$this->assertThat(
			trim($q->forUpdate),
			$this->equalTo('FOR UPDATE OF #__foo; #__bar'),
			'Tests rendered value.'
		);
	}

	/**
	 * Test for FOR SHARE clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testForShare ()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->forShare('#__foo'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->forShare),
			$this->equalTo('FOR SHARE OF #__foo'),
			'Tests rendered value.'
		);

		$q->forShare('#__bar');
		$this->assertThat(
			trim($q->forShare),
			$this->equalTo('FOR SHARE OF #__foo, #__bar'),
			'Tests rendered value.'
		);

		// Testing glue
		TestHelper::setValue($q, 'forShare', null);
		$q->forShare('#__foo', ';');
		$q->forShare('#__bar');
		$this->assertThat(
			trim($q->forShare),
			$this->equalTo('FOR SHARE OF #__foo; #__bar'),
			'Tests rendered value.'
		);
	}

	/**
	 * Test for NOWAIT clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testNoWait ()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->noWait(),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->noWait),
			$this->equalTo('NOWAIT'),
			'Tests rendered value.'
		);
	}

	/**
	 * Test for LIMIT clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testLimit()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->limit('5'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->limit),
			$this->equalTo('LIMIT 5'),
			'Tests rendered value.'
		);
	}

	/**
	 * Test for OFFSET clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testOffset()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->offset('10'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->offset),
			$this->equalTo('OFFSET 10'),
			'Tests rendered value.'
		);
	}

	/**
	 * Test for RETURNING clause.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testReturning()
	{
		$q = new \Joomla\Database\Postgresql\PostgresqlQuery($this->dbo);

		$this->assertThat(
			$q->returning('id'),
			$this->identicalTo($q),
			'Tests chaining.'
		);

		$this->assertThat(
			trim($q->returning),
			$this->equalTo('RETURNING id'),
			'Tests rendered value.'
		);
	}
}
