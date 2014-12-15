<?php
namespace SQLBuilder\Testing;
use SQLBuilder\Testing\QueryTestCase;
use SQLBuilder\ToSqlInterface;
use SQLBuilder\ArgumentArray;
use PHPUnit_Framework_TestCase;
use PDO;

/**
 * @package SQLBuilder
 *
 * @class PHPUnit_PDO_TestCase
 *
 * @author Yo-An Lin <yoanlin93@gmail.com>
 *
 * @code
 *
 *   class YourTest extends PHPUnit_PDO_TestCase
 *   {
 *
 *      // setup your database connection DSN (optional, default is sqlite memory)
 *      public $dsn = 'pgsql:tests';
 *
 *      // setup your database username (optional)
 *      public $user = 'postgres';
 *
 *      // setup your database password (optional)
 *      public $pass = 'postgres';
 *
 *
 *      // optional
 *      public $options = array( ... PDO connection options ... );
 *
 *      
 *      // provide your schema sql files
 *      public $schema = array( 
 *         'tests/schema/user.sql'
 *      );
 *
 *      // provide your fixture sql files
 *      public $fixture = array( 
 *          'tests/fixtures/file.sql',
 *      );
 *
 *   }
 */
abstract class PDOQueryTestCase extends QueryTestCase
{

    /**
     * @var PDO PDO connection handle
     */
    public $pdo;


    /**
     * @var string database connection string (DSN)
     */
    public $dsn;

    /**
     * @var string database username
     */
    public $user;

    /**
     * @var string database password
     */
    public $pass;


    /**
     * @var array PDO connection options
     */
    public $options;


    /**
     * @var array Schema files
     */
    public $schema;


    /**
     * @var string Schema directory path
     */
    public $schemaDir = 'tests/schema';


    /**
     * @var array Fixture files
     */
    public $fixture;


    /**
     * @var string Fixture directory path
     */
    public $fixtureDir = 'tests/fixture';

    public $driverType = 'MySQL';


    public function noPDOError()
    {
        $err = $this->pdo->errorInfo();
        ok( $err[0] === '00000' );
    }

    public function getDSN()
    {
        return $this->dsn ?: getenv( strtoupper($this->driverType) . '_DSN' );
    }

    public function getUser()
    {
        return $this->user ?: getenv( strtoupper($this->driverType) . '_USER');
    }

    public function getPass()
    {
        return $this->pass ?: getenv( strtoupper($this->driverType) . '_PASS');
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getDb()
    {
        return $this->pdo;
    }

    public function setUp()
    {
        if (! extension_loaded('pdo')) {
            return skip('pdo extension is required');
        }

        // XXX: check pdo driver
#          if( ! extension_loaded('pdo_pgsql') ) 
#              return skip('pdo pgsql required');

        if ( $this->getDSN() && $this->getUser() && $this->getPass() ) {
            $this->pdo = new PDO(
                $this->getDSN(),
                $this->getUser(),
                $this->getPass(),
                $this->getOptions() ?: null
            );
        } elseif ( $this->getDSN() && $this->getUser() ) {
            $this->pdo = new PDO( $this->getDSN(), $this->getUser() );
        } elseif ( $this->getDSN() ) {
            $this->pdo = new PDO( $this->getDSN() );
        } else {
            throw new Exception("Please define DSN for class: " . get_class($this) );
        }

        if ( ! $this->pdo ) {
            throw new Exception("Can not create PDO connection: " . get_class($this) );
        }

        // throw Exception on Error.
        $this->pdo->setAttribute( PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION );
        $this->setupSchema();

        ok($this->pdo);
    }

    public function setupSchema()
    {
        // get schema file (if we provide them)
        if( $this->schema ) {
            foreach( $this->schema as $file ) {

                // try to find schema file in schema directory
                if (! file_exists($file) ) {
                    if( file_exists($this->schemaDir . DIRECTORY_SEPARATOR . $file) ) {
                        $file = $this->schemaDir . DIRECTORY_SEPARATOR . $file;
                    }
                    else {
                        throw new Exception( "schema file $file not found." );
                    }
                }
                $content = file_get_contents($file);

                $statements = preg_split( '#;\s*$#ms', $content );
                foreach( $statements as $statement )  {
                    $this->queryOk(trim($statement));
                }
            }

        }

        // get schema from class method, which is SQL. 
        // then send query
        if( $sqls = $this->schema() ) {
            foreach( $sqls as $sql ) {
                $this->pdo->query($sql);
            }
        }
        // well done!
    }

    public function setupFixture()
    {
        if( $this->fixture ) {
            foreach( $this->fixture as $file ) {

                if (! file_exists($file) ) {
                    if( file_exists($this->fixtureDir . DIRECTORY_SEPARATOR . $file) ) {
                        $file = $this->fixtureDir . DIRECTORY_SEPARATOR . $file;
                    }
                    else {
                        throw new Exception( "fixture file $file not found." );
                    }
                }


                $content = file_get_contents($file);
                $statements = preg_split( '#;\s*$#', $content );
                foreach( $statements as $statement ) {
                    $this->queryOk($statement);
                }
            }
        }
    }

    public function testConnection() 
    {
        $this->assertInstanceOf('PDO', $this->pdo);
    }

    public function schema()
    {
        return;
    }

    public function assertQuery(ToSqlInterface $query, $message = '') {
        $driver = $this->createDriver();
        $args = new ArgumentArray;
        $sql = $query->toSql($driver, $args);
        $this->queryOk($sql, $args->toArray());
        return $args;
    }


    public function query($sql, array $args = array())
    {
        if ($args) {
            $stm = $this->pdo->prepare( $sql )->execute( $args );
        } else {
            $stm = $this->pdo->query( $sql );
        }
        $this->noPDOError();
        return $stm;
    }

    /**
     * Test Query
     *
     * @param string $sql SQL statement.
     * @param array $args Arguments for executing SQL statement.
     */
    public function queryOk($sql, array $args = array())
    {
        if ($args) {
            $stm = $this->pdo->prepare( $sql )->execute( $args );
        } else {
            $stm = $this->pdo->query( $sql );
        }
        $this->noPDOError();
        return $stm;
    }

    public function executeOk($sql,$args)
    {
        $stm = $this->pdo->prepare($sql);
        $err = $this->pdo->errorInfo();

        ok( ! $err[1] , $err[0] );

        ok( $stm );
        $stm->execute( $args );

        $err = $this->pdo->errorInfo();
        ok( ! $err[1] );
        return $stm;

    }

    public function recordOk($sql)
    {
        $stm = $this->queryOk($sql);
        $row = $stm->fetch();
        ok( $row );
        ok( ! empty( $row ));
        return $row;
    }

}
