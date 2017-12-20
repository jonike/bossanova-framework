/**
 * (c) 2017 Bossanova NodeJs Framework 1.0.1
 * http://bossanova.uk/nodejs
 *
 * @category PHP
 * @package  BossanovaJS
 * @author   Paul Hodel <paul.hodel@gmail.com>
 * @license  The MIT License (MIT)
 * @link     http://bossanova.uk/js
 */

class Database {
    // No actions
    constructor() {
        console.log('sadfsadfsadfsadfsadfsadfsadf');
    }

    static get(instanceName, connectionInfo) {
        if (! this.instance) {
            this.instance = []; 
        }

        if (! this.instance[instanceName]) {
            this.instance[instanceName] = connectionInfo;
        }
 
        return this.instance[instanceName];
    }
    

    /**
     * Set schema
     *
     * @param  string id
     * @return void
     */
    setSchema(schema)
    {
        this.connection->exec('SET search_path TO ' . schema);
    }

    /**
     * Set the database debug mode on/off
     * @param boolean mode true or false
     * @return void
     */
    setDebug(mode)
    {
        this.debug = (mode) ? true : false;
    }

    /**
     * Set the database email debug mode on/off. This method allows the developer to receive an email
     * with all debug information when an SQL error occours.
     * @param boolean mode true or false
     * @return void
     */
    setDebugEmail(email)
    {
        this.debug_email = email;
    }

    /**
     * Bind the input parameters
     * @param string val Param content
     * @param boolean database_function Do nothing because it is a database_function
     * @return string val Content string binded
     */
    bind(val)
    {
        if (is_array(val)) {
            if (count(val)) {
                data = array();

                foreach (val as k => v) {
                    if (trim(v) === "") {
                        data[k] = "null";
                    } else {
                        if (strtoupper(v) == 'NOW()') {
                            data[k] = v;
                        } elseif (gettype(v) == "string") {
                            // Protection against SQL injection
                            data[k] = "'". str_replace("'", "", v) . "'";
                        } else {
                            data[k] = str_replace("'", "", v);
                        }
                    }
                }

                // Final array with complete data
                val = data;
            }
        } elseif (trim(val) === "") {
            val = "null";
        } elseif (strtoupper(trim(val)) == 'NOW()') {
            val = "NOW()";
        } else {
            if (typeof(val) == "string") {
                // Protection against SQL injection
                val = "'". str_replace("'", "", val) . "'";
            } else {
                val = str_replace("'", "", val);
            }
        }

        return val;
    }

    /**
     * Keep the table reference name to assembly the query
     *
     * @param string table Table name
     * @return self
     */
    table(tableName)
    {
        // Reset all
        this.query = [];

        // Set tablename
        this.query['table'] = tableName;

        return this;
    }

    /**
     * Keep the colums names to assembly the query
     *
     * @param  mixed column string for Select or array for Insert and Updates
     * @return void
     */
    column(column)
    {
        this.query['column'] = column;

        return this;
    }

    /**
     * Keep the left join string to assembly the query
     *
     * @param  string tableName
     * @param  string arguments
     * @return void
     */
    leftJoin(tableName, arguments)
    {
        if (! this.query['join']) {
            this.query['join'] = '';
        }

        this.query['join'] += " LEFT JOIN " + tableName + " ON (" + arguments + ")";

        return this;
    }

    /**
     * Keep the right join string to assembly the query
     *
     * @param  string tableName
     * @param  string arguments
     * @return void
     */
    rightJoin(tableName, arguments)
    {
        if (! this.query['join']) {
            this.query['join'] = '';
        }

        this.query['join'] += " RIGHT JOIN " + tableName + " ON (" + arguments + ")";

        return this;
    }

    /**
     * Keep the inner join string to assembly the query
     *
     * @param  string tableName
     * @param  string arguments
     * @return void
     */
    innerJoin(tableName, arguments)
    {
        if (! this.query['join']) {
            this.query['join'] = '';
        }

        this.query['join'] += " INNER JOIN " + tableName + " ON (" + arguments + ")";

        return this;
    }

    /**
     * Keep the group by string to assembly the query
     *
     * @param  string groupBy
     * @return void
     */
    group(groupBy)
    {
        this.query['group'] = groupBy;

        return this;
    }

    /**
     * Keep the order by string to assembly the query
     *
     * @param  string order Order by string
     * @return void
     */
    order(orderBy)
    {
        this.query['order'] = orderBy;

        return this;
    }

    /**
     * Keep the limit by string to assembly the query
     * @param  string limit
     * @return void
     */
    limit(limit)
    {
        this.query['limit'] = limit;

        return this;
    }

    /**
     * Offset
     * @param  string offset
     * @return void
     */
    offset(offset)
    {
        this.query['offset'] = offset;

        return this;
    }

    /**
     * Keep the having by string to assembly the query
     *
     * @param string having having
     * @return void
     */
    having(having)
    {
        this.query['having'] = having;

        return this;
    }

    /**
     * Keep the array of arguments to assembly the where in the query
     * @param string i Number of the argument
     * @param string k Column name
     * @param mixed v Number or string value
     * @param string o Operator (default is an equal)
     * @return void
     */
    argument(i, k, v, o)
    {
        if (! o) {
            o = ' = ';
        }

        this.query['argument'][i] = k + ' ' + o + ' ' + v;

        return this;
    }

    /**
     * Assembly the where with the arguments saved
     * @param string where Logical argument distribution in the where, ex. ((1) OR (2)) AND (3)
     * @return void
     */
    where(where = null)
    {
        if (where) {
        // Create custom logical operations based on the indexes. example: ((1) OR (2)) AND (3)
            if (isset(this.query['argument'])) {
            // Necessary operation to avoid brackets clash with SQL arguments
                this.query['where'] = where;
                this.query['where'] = str_replace("(", "[[", this.query['where']);
                this.query['where'] = str_replace(")", "]]", this.query['where']);

                foreach (this.query['argument'] as k => v) {
                // Replace each argument in the logical defined string in the input of this method
                    this.query['where'] = str_replace("[[k]]", "(v)", this.query['where']);
                }

                // Make sure to return the original syntax
                this.query['where'] = str_replace("[[", "(", this.query['where']);
                this.query['where'] = str_replace("]]", ")", this.query['where']);
            }
        } else {
            // Default is an AND between all arguments
            where = '';

            if (this.query['argument']) {
                foreach (this.query['argument'] as k => v) {
                    if (where) {
                        where .= " AND ";
                    }

                    where .= "(v)";
                }
            }

            this.query['where'] = where;
        }

        return this;
    }

    /**
     * Define a manual query to be executed
     * @param string query Manual complete query
     * @return void
     */
    setQuery(query)
    {
        this.query['query'] = query;

        return this;
    }

    /**
     * Return the query already in this instance
     * @param void
     * @return string query Complete assembled query considering all inputs using other methods
     */
    getQuery()
    {
        query = "";

        if (this.query['query']) {
            query = this.query['query'];
        }

        return query;
    }

    /**
     * Assembly a new SELECT usign all definitions
     * @param void
     * @return    (string) stringSQL Statment
     */
    select()
    {
        // Create select statement based on the arguments defined so far

        if (!isset(this.query['column'])) {
            this.query['query'] = "SELECT *";
        } else {
            if(is_array(this.query['column'])){
                this.query['column'] = implode(',',this.query['column']);
            }
            this.query['query'] = "SELECT " . this.query['column'];
        }

        if (!isset(this.query['where'])) {
            if (isset(this.query['argument']) && count(this.query['argument'])) {
                this.Where();
            }
        }

        if (isset(this.query['table'])) {
            this.query['query'] .= " FROM " . this.query['table'];
        }
        if (isset(this.query['join'])) {
            this.query['query'] .= " " . this.query['join'];
        }
        if (isset(this.query['where'])) {
            this.query['query'] .= " WHERE " . this.query['where'];
        }
        if (isset(this.query['group'])) {
            this.query['query'] .= " GROUP BY " . this.query['group'];
        }
        if (isset(this.query['having'])) {
            this.query['query'] .= " HAVING " . this.query['having'];
        }
        if (isset(this.query['order'])) {
            this.query['query'] .= " ORDER BY " . this.query['order'];
        }
        if (isset(this.query['limit'])) {
            this.query['query'] .= " LIMIT " . this.query['limit'];
        }
        if (isset(this.query['offset'])) {
            this.query['query'] .= " OFFSET " . this.query['offset'];
        }

        return this;
    }

    /**
     * Assembly a new SELECT usign all definitions and return the complete SELECT SQL
     * @param void
     * @return void
     */
    getSelect()
    {
        this.select();

        return this.query['query'];
    }

    /**
     * Assembly a new INSERT usign all definitions
     * @param void
     * @return    (string) stringSQL Statment
     */
    insert()
    {
        // Create insert statement based on the arguments defined so far
        this.query['names'] = "";
        this.query['values'] = "";

        foreach (this.query['column'] as k => v) {
        // Null values
            if (v === '') {
                v = "null";
            }

            // Insert values
            if (this.query['names'] != "") {
                this.query['names'] .= ", ";
            }
            this.query['names'] .= "k";

            if (this.query['values'] != "") {
                this.query['values'] .= ", ";
            }
            this.query['values'] .= "v";
        }

        this.query['query'] = "INSERT INTO " . this.query['table'] . " (" . this.query['names'] . ")
            VALUES (" . this.query['values'] . ")";

        return this;
    }

    /**
     * Assembly a new INSERT usign all definitions and return the complete INSERT SQL
     * @param void
     * @return    (string) stringSQL Statment
     */
    getInsert()
    {
        this.insert();

        return this.query['query'];
    }

    /**
     * Assembly a new UPDATE usign all definitions
     * @param    (void)
     * @return    (string) stringSQL Statment
     */
    update()
    {
        // Create update statement based on the arguments defined so far
        this.query['set'] = '';

        foreach (this.query['column'] as k => v) {
            if (this.query['set'] != "") {
                this.query['set'] .= ", ";
            }

            this.query['set'] .= "k = v";
        }

        this.query['query'] = "UPDATE "  .this.query['table'] . " SET " . this.query['set'];

        if (!isset(this.query['where'])) {
            if (isset(this.query['argument']) && count(this.query['argument'])) {
                this.Where();
            }
        }

        if (isset(this.query['where'])) {
            this.query['query'] .= " WHERE " . this.query['where'];
        }

        return this;
    }

    /**
     * Assembly a new UPDATE usign all definitions and return the complete UPDATE SQL
     * @param    (void)
     * @return    (string) stringSQL Statment
     */
    getUpdate()
    {
        this.update();

        return this.query['query'];
    }

    /**
     * Assembly a new DELETE usign all definitions
     * @param    (void)
     * @return    (string) stringSQL Statment
     */
    delete()
    {
        // Create delete statement based on the arguments defined so far

        this.query['query'] = "DELETE FROM " . this.query['table'];

        if (!isset(this.query['where'])) {
            if (isset(this.query['argument']) && count(this.query['argument'])) {
                this.Where();
            }
        }

        if (isset(this.query['where'])) {
            this.query['query'] .= " WHERE " . this.query['where'];
        }

        return this;
    }

    /**
     * Assembly a new DELETE usign all definitions and return the complete DELETE SQL
     * @param    (void)
     * @return    (string)    SQL Statment
     */
    getDelete()
    {
        this.delete();

        return this.query['query'];
    }

    /**
     * Check if the record exists and decide between insert and update
     * @param    (void)
     * @return    (string)    SQL Statment
     */
    checkAndSave(debug = null, pk = null, sq = null)
    {
        id = 0;

        this.select();
        result = this.execute(debug);

        if (row = this.fetch_assoc(result)) {
            if (isset(row[pk])) {
                id = row[pk];
            }
            this.update();
            this.execute(debug);
        } else {
            this.insert();
            result = this.execute(debug);
            id = this.insert_id(sq);
        }

        return id;
    }

    /**
     * Begin transaction
     * @param void
     * @return void
     */
    begin()
    {
        this.connection->beginTransaction();
    }

    /**
     * Comite the transaction
     * @param void
     * @return void
     */
    commit()
    {
        this.connection->commit();
    }

    /**
     * Rollback the transaction
     * @param void
     * @return void
     */
    rollBack()
    {
        this.connection->rollBack();
    }

    /**
     * Execute the query in the memory
     * @param int debug 0 => Normal execution
     *                      1 => Return the query without any execution
     *                      2 => Execute and print the query and print any erros
     *                      3 => Execute the query and print any erros and exit the script execution
     * @return resource result Query result #Resource
     */
    execute(debug = 0)
    {
        // Check status from global debug
        if (self::debug == true) {
            debug = 2;
        }

        // In case no statement was created, assume it is a select statement
        if (!isset(this.query['query']) || !this.query['query']) {
            this.select();
        }

        // Prepare and execute query
        if (debug == 1) {
            result = this.query['query'];
        } else {
            result = this.connection->prepare(this.query['query']);

            i = microtime(true);
            result->execute();
            f = microtime(true);
            row = result->errorInfo();

            if (debug == 2) {
            // Debug mode two show SQL debug information
                t = f - i;

                echo this.query['query'] . "<br>\n(t)<br>\n". row[1] . " " . row[2];
            } elseif (debug == 3) {
            // Debug mode three interrupt the script if any error is found
                if (row[1] && row[2]) {
                    t = f - i;

                    echo this.query['query'] . "<br>\n(t)<br>\n". row[1] . " " . row[2];

                    exit;
                }
            }

                    // Check if there is any error in the SQL
            if (row[1] && row[2]) {
                if (this.error != row[1] . " " . row[2]) {
                    // If is defined any email, send this error by email
                    if (self::debug_email) {
                        email = self::debug_email;

                        server = isset(_SERVER['SERVER_NAME']) ? _SERVER['SERVER_NAME'] : '';

                        // Tracking SQL execution time
                        t = f - i;

                        // Get debug string information
                        debug_text = this.errorInformation();

                        // Preparent email content
                        text = this.query['query'] . "<br>\n(t)<br>\n". row[1] . " " . row[2] . "debug_text\n";

                        // Send debug email
                        mail(email, "Bossanova::debug (server)", "text", "From:email\r\n");
                    }
                }

                // Set global error
                this.error = row[1] . " " . row[2];
            }
        }

        return result;
    }

    errorInformation()
    {
        // Debug information string
        trace = debug_backtrace();

        // String to be returned
        debug_text  = "\n<br>";
        debug_text .= "GET<br>" . print_r(_GET, true);
        debug_text .= "POST<br>" . print_r(_POST, true);
        debug_text .= "SERVER<br>" . print_r(_SERVER, true);
        debug_text .= "DEBUG<br>" . print_r(trace, true);

        if (isset(_SESSION)) {
            debug_text .= "SESSION<br>" . print_r(_SESSION, true);
        }

        return debug_text;
    }

    /**
     * Return the numbers of rows from the select
     * @param string pk primary key to base the counting
     * @return intenger total total number
     */
    rows(pk)
    {
        // Quantity of rows: postgresql compatibility
        query = "SELECT COUNT(code) AS total FROM (SELECT pk AS code";

        if (isset(this.query['table'])) {
            query .= " FROM " . this.query['table'];
        }
        if (isset(this.query['join'])) {
            query .= " " . this.query['join'];
        }
        if (isset(this.query['where'])) {
            query .= " WHERE " . this.query['where'];
        }
        query .= " GROUP BY pk) t";

        result = this.connection->prepare(query);
        result->execute();
        row = this.fetch_assoc(result);

        return row['total'];
    }

    /**
     * Return the last id based on the sequence for postgresql or get last id for mysql
     * @param string sequence used for postgres
     * @return intenger return the for the inserted record
     */
    insert_id(result = null)
    {
        // Mysql and PostgreSQL have a different approach.
        if (is_string(result)) {
            id = this.connection->lastInsertId(result);
        } else {
            id = this.connection->lastInsertId();
        }

        return id;
    }

    /**
     * Return the record fetched in an associative array
     * @param resource result resource from the execution
     * @return array row2 associative array with all the record
     */

    fetch_assoc(result)
    {
        return result->fetch(\PDO::FETCH_ASSOC);
    }
    /**
     * Return the record fetched in an associative array
     * @param resource result resource from the execution
     * @return array row2 associative array with all the record
     */

    fetch_row(result)
    {
        return result->fetch(\PDO::FETCH_NUM);
    }

    /**
     * Return all records in a multiple array
     * @param resource result resource from the execution
     * @return array row2 associative array with all the records
     */
    fetch_assoc_all(result)
    {
        return result->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Return the record number
     * @param mixed result resource to be used on mysql and a string to count the records for postgresql
     * @return integer number of records
     */
    num_rows(result)
    {
        if (is_string(result)) {
            return this.rows(result);
        } else {
            return result->fetchColumn();
        }
    }

    /**
     * Get the table information
     *
     * @param  string tableName
     * @return mixed  tableInfo
     */
    getTableInfo(tableName)
    {
        row = null;

        // Find primary key and keep in the session for future use
        if (DB_CONFIG_TYPE == 'mysql') {
            this.setQuery("SHOW KEYS FROM tableName WHERE Key_name = 'PRIMARY'");
            result = this.execute();
            row = this.fetch_assoc(result);
        } elseif (DB_CONFIG_TYPE == 'pgsql') {
            query = "SELECT * FROM information_schema.table_constraints tc
                JOIN information_schema.constraint_column_usage AS ccu USING (constraint_schema, constraint_name)
                JOIN information_schema.columns AS c ON c.table_schema = tc.constraint_schema
                AND tc.table_name = c.table_name AND ccu.column_name = c.column_name
                WHERE constraint_type = 'PRIMARY KEY' and tc.table_name = 'tableName'";
            this.setQuery(query);
            result = this.execute();
            row = this.fetch_assoc(result);
        }

        return row;
    }

    /**
     * Get the primary key from the defined table
     *
     * @param  string tableName
     * @return string primaryKey
     */
    getPrimaryKey(tableName)
    {
        // Get the table info
        column_name = null;
        if (row = this.getTableInfo(tableName)) {
            column_name = isset(row['Column_name']) ? row['Column_name'] : row['column_name'];
        }

        return column_name;
    }

    /**
     * Create a model on the fly if the table exists
     * @param string table table name
     * @return object instance from a model
     */
    model(tableName)
    {
        if (this.getTableInfo(tableName)) {
            return new Model(this, tableName);
        } else {
            return null;
        }
    }

    /**
     * Find a model
     * @param string table table name
     * @return object instance from a model
     */
    find(name, id = null)
    {
        if (this.getTableInfo(name)) {
            model = new Model(this, name);
            if (id) {
                model->get(id);
            }
        } else {
            model = false;
        }

        return model;
    }

    /**
     * Create automatic models in case table match name
     * @param unknown name
     * @param unknown value
     */
    __get(name)
    {
        if (this.getTableInfo(name)) {
            model = new Model(this, name);
        } else {
            model = false;
        }

        return model;
    }
}

module.exports = Database;