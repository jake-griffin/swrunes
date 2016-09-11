<?php
include("config.php");

//----------------------------------------------------------------------------------
// class DBWrapper
//----------------------------------------------------------------------------------
class Wrapper
{
    private $db;
    private $username;
    private $password;
    private $host;
    private $port;
    private $dbname;

    private $in_transaction;

    public function __construct($a_username, $a_password, $a_host, $a_dbname, $a_port=null)
    {
        $this->username = $a_username;
        $this->password = $a_password;
        $this->host = $a_host;
        $this->dbname = $a_dbname;
        $this->port = $a_port;
        $this->in_transaction = false;
    }

    public function close()
    {
        $this->db = null;
    }

    public function beginTransaction()
    {
        if(!$this->in_transaction)
        {
            $this->in_transaction= true;
            $this->db = $this->getDatabase();
            $this->db->beginTransaction();
        }
    }

    public function commit()
    {
        if($this->in_transaction)
        {
            $this->in_transaction = false;
            $this->db = $this->getDatabase();
            $this->db->commit();
        }
    }

    public function rollBack()
    {
        if($this->in_transaction)
        {
            $this->in_transaction = false;
            $this->db = $this->getDatabase();
            $this->db->rollBack();
        }
    }

    public function inTransaction()
    {
        return $this->in_transaction;
    }

    public function getLastInsertId()
    {
        $this->db = $this->GetDatabase();
        return $this->db->lastInsertId();
    }

    private function getDatabase()
    {
        if ($this->db === null) {
            try {
                $port_string="";
                if(isset($this->port) && !empty($this->port))
                    $port_string=";port=".$this->port;
                $this->db = new PDO("mysql:host=".$this->host.$port_string.";dbname=".$this->dbname.";charset=utf8", $this->username, $this->password);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->db->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
                $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                $this->db->setAttribute(PDO::MYSQL_ATTR_LOCAL_INFILE, 1);
            }
            catch(PDOException $e) {
                throw new Exception('Unable to connect to Database! Error message: '.$e->getMessage());
                //die ('Unable to connect to Database! Error message: '.$e->getMessage());
            }
        }
        return $this->db;
    }

    public function newOptimization($session, $monster_id, &$data)
    {
        $step = 10000;
        $total = count($data);
        $i = 0;
        $rowCount = 0;
        while($i<$total) {
            try {
                $values = "";
                $i1 = 0;
                while( $i + $i1 < $total && $i1 < $step) {
                    $index = $i + $i1;
                    if(!empty($values)) {
                        $values .= ",";
                    }
                    $row = $data[$index];
                    $values .= "('{$session}','{$monster_id}','{$row["rune_ids"]}','{$row["sets"]}',{$row["a_hp"]},{$row["a_atk"]},{$row["a_def"]},{$row["a_spd"]},{$row["a_crate"]},{$row["a_cdmg"]},{$row["a_res"]},{$row["a_acc"]},'{$row["a_dps"]}','{$row["a_dpa"]}','{$row["a_effhp"]}','{$row["a_effhp_d"]}',{$row["m_hp"]},{$row["m_atk"]},{$row["m_def"]},{$row["m_spd"]},{$row["m_crate"]},{$row["m_cdmg"]},{$row["m_res"]},{$row["m_acc"]},'{$row["m_dps"]}','{$row["m_dpa"]}','{$row["m_effhp"]}','{$row["m_effhp_d"]}','{$row["slots246"]}','{$row["substat_skillups"]}')";
                    $i1 += 1;
                }
                $this->db = $this->getDatabase();
                $count = $this->db->exec('INSERT INTO sw_optimizer (session, `monster_id`, `rune_ids`, `sets`, `a_hp`, `a_atk`, `a_def`, `a_spd`, `a_crate`, `a_cdmg`, `a_res`, `a_acc`, `a_dps`, `a_dpa`, `a_effhp`, `a_effhp_d`, `m_hp`, `m_atk`, `m_def`, `m_spd`, `m_crate`, `m_cdmg`, `m_res`, `m_acc`, `m_dps`, `m_dpa`, `m_effhp`, `m_effhp_d`, `slots246`, `substat_skillups`) VALUES '.$values);
                $rowCount = $rowCount + $count;
            }
            catch(PDOException $e) {
                //$e->getMessage();
                return $rowCount;
            }
            $i = $i + $i1;
        }
        return $rowCount;
    }

    public function newOptimizationFromFile($filename)
    {
        try {
            $this->db = $this->getDatabase();
            $count = $this->db->exec(
                "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE sw_optimizer
                FIELDS TERMINATED BY ';'
                LINES TERMINATED BY '\n'
                (session, monster_id, rune_ids, sets, a_hp, a_atk, a_def, a_spd, a_crate, a_cdmg, a_res, a_acc, a_dps, a_dpa, a_effhp, a_effhp_d, m_hp, m_atk, m_def, m_spd, m_crate, m_cdmg, m_res, m_acc, m_dps, m_dpa, m_effhp, m_effhp_d, slots246, substat_skillups) ");
            return $count;
        }
        catch(PDOException $e) {
            throw new Exception('Unable to load file '.$filename.'! Error message: '.$e->getMessage());
            //$e->getMessage();
        }
        return 0;
    }

    public function Maintenance()
    {
        try {

            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
                'OPTIMIZE TABLE `sw_optimizer` ');
            $statm->execute();
            return $statm->fetchAll();
        }
        catch(PDOException $e) {
            //$e->getMessage();
        }
        return array();
    }

    /*public function deleteOld($minutes)
    {
        try {
            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
            "DELETE FROM sw_optimizer
            WHERE created < DATE_SUB(NOW(), INTERVAL ".$minutes." MINUTE)
            LIMIT 10000 ");
            $cnt = 1;
            while($cnt > 0) {
                $statm->execute();
                $cnt = $statm->rowCount();
            }
            return $statm->rowCount();
        }
        catch(PDOException $e) {
            //$e->getMessage();
            }
        return 0;
    }*/


    public function deleteBySession($session, $optimize_run)
    {
        try {
            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
                "DELETE FROM sw_optimizer
                WHERE session = ? AND optimize_run <> ?
                LIMIT 10000 ");
            $cnt = 1;
            while($cnt > 0) {
                $statm->execute(array($session, $optimize_run));
                $cnt = $statm->rowCount();
            }
            return $statm->rowCount();
        }
        catch(PDOException $e) {
            //$e->getMessage();
        }
        return 0;
    }

    public function getOptimization($session, $post, $search, $orderBy, $orderWay, $start, $count, $count_only=false)
    {
        if( $orderWay != "asc" && $orderWay != "desc" && $orderWay != "ASC" && $orderWay != "DESC") {
            if($count_only){
                return array( array("cnt"=>0));
            }
            else {
                return array();
            }
        }

        $cond = "";
        $limit = "";
        $select = "";
        $orderby = "";
        $params[] = $session;
        $filter_type = "a";
        if( !empty($post["filter_type_div"]) && $post["filter_type_div"] == "m") {
            $filter_type = "m";
        }

        if( !empty($post["filter_min_hp"]) )
        {
            $cond .= " AND ".$filter_type."_hp >= ? ";
            $params[] = $post["filter_min_hp"];
        }
        if( !empty($post["filter_max_hp"]) )
        {
            $cond .= " AND ".$filter_type."_hp <= ? ";
            $params[] = $post["filter_max_hp"];
        }
        if( !empty($post["filter_min_atk"]) )
        {
            $cond .= " AND ".$filter_type."_atk >= ? ";
            $params[] = $post["filter_min_atk"];
        }
        if( !empty($post["filter_max_atk"]) )
        {
            $cond .= " AND ".$filter_type."_atk <= ? ";
            $params[] = $post["filter_max_atk"];
        }
        if( !empty($post["filter_min_def"]) )
        {
            $cond .= " AND ".$filter_type."_def >= ? ";
            $params[] = $post["filter_min_def"];
        }
        if( !empty($post["filter_max_def"]) )
        {
            $cond .= " AND ".$filter_type."_def <= ? ";
            $params[] = $post["filter_max_def"];
        }
        if( !empty($post["filter_min_spd"]) )
        {
            $cond .= " AND ".$filter_type."_spd >= ? ";
            $params[] = $post["filter_min_spd"];
        }
        if( !empty($post["filter_max_spd"]) )
        {
            $cond .= " AND ".$filter_type."_spd <= ? ";
            $params[] = $post["filter_max_spd"];
        }
        if( !empty($post["filter_min_crate"]) )
        {
            $cond .= " AND ".$filter_type."_crate >= ? ";
            $params[] = $post["filter_min_crate"];
        }
        if( !empty($post["filter_max_crate"]) )
        {
            $cond .= " AND ".$filter_type."_crate <= ? ";
            $params[] = $post["filter_max_crate"];
        }
        if( !empty($post["filter_min_cdmg"]) )
        {
            $cond .= " AND ".$filter_type."_cdmg >= ? ";
            $params[] = $post["filter_min_cdmg"];
        }
        if( !empty($post["filter_max_cdmg"]) )
        {
            $cond .= " AND ".$filter_type."_cdmg <= ? ";
            $params[] = $post["filter_max_cdmg"];
        }
        if( !empty($post["filter_min_res"]) )
        {
            $cond .= " AND ".$filter_type."_res >= ? ";
            $params[] = $post["filter_min_res"];
        }
        if( !empty($post["filter_max_res"]) )
        {
            $cond .= " AND ".$filter_type."_res <= ? ";
            $params[] = $post["filter_max_res"];
        }
        if( !empty($post["filter_min_acc"]) )
        {
            $cond .= " AND ".$filter_type."_acc >= ? ";
            $params[] = $post["filter_min_acc"];
        }
        if( !empty($post["filter_max_acc"]) )
        {
            $cond .= " AND ".$filter_type."_acc <= ? ";
            $params[] = $post["filter_max_acc"];
        }
        if( !empty($search) )
        {
            $cond .= " AND ( rune_ids like CONCAT('%',?,'%') OR UPPER(sets) like CONCAT('%',?,'%') OR UPPER(slots246) like CONCAT('%',?,'%') ) ";
            $escaped_upper = strtoupper($search);
            $params[] = $escaped_upper;
            $params[] = $escaped_upper;
            $params[] = $escaped_upper;
        }


        if($count_only)
        {
            $select = "COUNT(*) AS cnt";
        }
        else
        {
            $select = "rune_ids, sets, `a_hp`, `a_atk`, `a_def`, `a_spd`, `a_crate`, `a_cdmg`, `a_res`, `a_acc`, `a_dps`, `a_dpa`, `a_effhp`, `a_effhp_d`, `m_hp`, `m_atk`, `m_def`, `m_spd`, `m_crate`, `m_cdmg`, `m_res`, `m_acc`, `m_dps`, `m_dpa`, `m_effhp`, `m_effhp_d`, `slots246`, `substat_skillups`, `monster_id` ";

            $orderby = "ORDER BY ".$orderBy." ".$orderWay;
            if( !empty($start) && !empty($count))
            {
                $limit = " LIMIT ".$start.",".$count;
            }
            else if( empty($start) && !empty($count))
            {
                $limit = " LIMIT ".$count;
            }
        }

        try {
            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
                "SELECT ".$select."
                FROM sw_optimizer
                WHERE session = ? ".$cond." ".$orderby." ".$limit);
            $statm->execute($params);

            return $statm->fetchAll();
        }
        catch(PDOException $e) {
            //echo $e->getMessage();
        }
        return array();
    }

    public function getTotalRecords()
    {
        try {
            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
                "SELECT COUNT(*) AS cnt
                FROM sw_optimizer ");
            $statm->execute();

            return $statm->fetchAll();
        }
        catch(PDOException $e) {
            //echo $e->getMessage();
        }
        return array();
    }

    public function getUniqueSessions()
    {
        try {
            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
                "SELECT COUNT(*) as cnt from
                (SELECT DISTINCT session
                FROM sw_optimizer
            )
            ");
            $statm->execute();

            return $statm->fetchAll();
        }
        catch(PDOException $e) {
            //echo $e->getMessage();
        }
        return array();
    }

    public function getSessionsAndRecords()
    {
        try {
            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
                "SELECT session, count(*) as cnt
                FROM sw_optimizer
                GROUP BY session
                ORDER BY cnt desc");
            $statm->execute();

            return $statm->fetchAll();
        }
        catch(PDOException $e) {
            //echo $e->getMessage();
        }
        return array();
    }

    public function createNewOptimizerTableCopyData($minutes, $table)
    {
        try {
            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
                "CREATE TABLE IF NOT EXISTS ".$table." (
                    `session` varchar(20) COLLATE utf8_bin NOT NULL,
                    `optimize_run` varchar(3) COLLATE utf8_bin NOT NULL,
                    `rune_ids` varchar(50) COLLATE utf8_bin NOT NULL,
                    `sets` varchar(50) COLLATE utf8_bin DEFAULT NULL,
                    `a_hp` int(11) NOT NULL,
                    `a_atk` int(11) NOT NULL,
                    `a_def` int(11) NOT NULL,
                    `a_spd` int(11) NOT NULL,
                    `a_crate` int(11) NOT NULL,
                    `a_cdmg` int(11) NOT NULL,
                    `a_res` int(11) NOT NULL,
                    `a_acc` int(11) NOT NULL,
                    `a_dps` int(11) NOT NULL,
                    `a_dpa` int(11) NOT NULL,
                    `m_hp` int(11) NOT NULL,
                    `m_atk` int(11) NOT NULL,
                    `m_def` int(11) NOT NULL,
                    `m_spd` int(11) NOT NULL,
                    `m_crate` int(11) NOT NULL,
                    `m_cdmg` int(11) NOT NULL,
                    `m_res` int(11) NOT NULL,
                    `m_acc` int(11) NOT NULL,
                    `m_dps` int(11) NOT NULL,
                    `m_dpa` int(11) NOT NULL,
                    `slots246` varchar(50) COLLATE utf8_bin NOT NULL,
                    `substat_skillups` int(11) DEFAULT NULL,
                    `monster_id` int(11) NOT NULL,
                    KEY `session_run` (`session`,`optimize_run`),
                    KEY `sets` (`sets`),
                    KEY `slots246` (`slots246`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin
                AS SELECT * from sw_optimizer WHERE created >= DATE_SUB(NOW(), INTERVAL ".$minutes." MINUTE) ");
            $statm->execute();

            return $statm->fetchAll();
        }
        catch(PDOException $e) {
            //echo $e->getMessage();
        }
        return array();
    }

    public function createNewOptimizerTable($table)
    {
        try {
            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
                "CREATE TABLE IF NOT EXISTS ".$table." (
                    `session` int(9) NOT NULL,
                    `rune_ids` varchar(50) COLLATE utf8_bin NOT NULL,
                    `sets` varchar(50) COLLATE utf8_bin DEFAULT NULL,
                    `a_hp` int(11) NOT NULL,
                    `a_atk` int(11) NOT NULL,
                    `a_def` int(11) NOT NULL,
                    `a_spd` int(11) NOT NULL,
                    `a_crate` int(11) NOT NULL,
                    `a_cdmg` int(11) NOT NULL,
                    `a_res` int(11) NOT NULL,
                    `a_acc` int(11) NOT NULL,
                    `a_dps` int(11) NOT NULL,
                    `a_dpa` int(11) NOT NULL,
                    `a_effhp` int(11) NOT NULL,
                    `a_effhp_d` int(11) NOT NULL,
                    `m_hp` int(11) NOT NULL,
                    `m_atk` int(11) NOT NULL,
                    `m_def` int(11) NOT NULL,
                    `m_spd` int(11) NOT NULL,
                    `m_crate` int(11) NOT NULL,
                    `m_cdmg` int(11) NOT NULL,
                    `m_res` int(11) NOT NULL,
                    `m_acc` int(11) NOT NULL,
                    `m_dps` int(11) NOT NULL,
                    `m_dpa` int(11) NOT NULL,
                    `m_effhp` int(11) NOT NULL,
                    `m_effhp_d` int(11) NOT NULL,
                    `slots246` varchar(50) COLLATE utf8_bin NOT NULL,
                    `substat_skillups` int(11) DEFAULT 0,
                    `monster_id` int(11) NOT NULL,
                    KEY `session_run` (`session`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ");
            $statm->execute();

            return $statm->fetchAll();
        }
        catch(PDOException $e) {
            //echo $e->getMessage();
        }
        return array();
    }

    public function dropTable($table)
    {
        try {
            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
                "DROP TABLE ".$table);
            $statm->execute();

            return $statm->fetchAll();
        }
        catch(PDOException $e) {
            //echo $e->getMessage();
        }
        return array();
    }

    public function renameTable($old_name, $new_name)
    {
        try {
            $this->db = $this->getDatabase();
            $statm = $this->db->prepare(
                "RENAME TABLE ".$old_name." TO ".$new_name);
            $statm->execute();

            return $statm->fetchAll();
        }
        catch(PDOException $e) {
            //echo $e->getMessage();
        }
        return array();
    }

}
?>
