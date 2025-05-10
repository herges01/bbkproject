		<?php
		#Your PHP solution code should go here...
		$host = 'mysqlsrv.dcs.bbk.ac.uk';    #host name
        $db   = 'herges01db';                 #database name which is your selected/default schema
        $user = 'herges01';                #username loginID
        $pass = 'bbkmysql';                    #password
        $charset = 'utf8mb4';                #define character set to be used
        #create the Data Source Name for a MySQL connection using above variables
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        #driver-specific connection options
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        #CONNECT to the database
        try {
                $pdo = new PDO($dsn, $user, $pass, $options);
        } 
		catch (PDOException $e) {
                $errorMessage = $e->getMessage();
                echo "</p>FAILED to Connect to database: $db</p>";
                echo "</p>check your connection parameters.</p>";
                echo "</p>PDOexception message: $errorMessage</p>";
        }
		?>