<?php
/**
 * @Author: printempw
 * @Date:   2016-03-13 14:59:32
 * @Last Modified by:   printempw
 * @Last Modified time: 2016-03-18 16:42:37
 */

class DiscuzDatabase extends Database implements EncryptInterface, SyncInterface
{
    protected $table_name;
    protected $column_uname;
    protected $column_passwd;
    protected $column_ip;

    function __construct() {
        parent::__construct();
        $this->table_name    = Config::get('data_table_name');
        $this->column_uname  = Config::get('data_column_uname');
        $this->column_passwd = Config::get('data_column_passwd');
        $this->column_ip     = Config::get('data_column_ip');
    }

    /**
     * Discuz's Fucking dynamic salt
     */
    public function encryptPassword($raw_passwd, $username="") {
        $salt = $this->query("SELECT * FROM ".$this->table_name."
            WHERE ".$this->column_uname."='$username'")->fetch_array()['salt'];
        $encrypt = md5(md5($raw_passwd).$salt);
        return $encrypt;
    }

    public function createRecord($username, $password, $ip) {
        $sql = "INSERT INTO ".$this->table_name." (".$this->column_uname.", ".$this->column_passwd.", ".$this->column_ip.")
                VALUES ('$username', '$password', '$ip')";
        return $this->query($sql);
    }

    public function sync($username) {
        $exist_in_bs_table = $this->checkRecordExist('username', $username);
        $exist_in_discuz_table = ($this->query("SELECT * FROM ".$this->table_name."
            WHERE ".$this->column_uname."='$username'")->num_rows) ? true : false;

        if ($exist_in_bs_table && !$exist_in_discuz_table) {
            $result = $this->select('username', $username);
            $this->createRecord($username, $result['password'], $result['ip']);
            return $this->sync($username);
        }

        if (!$exist_in_bs_table && $exist_in_discuz_table) {
            $result = $this->query("SELECT * FROM ".$this->table_name."
                WHERE ".$this->column_uname."='$username'")->fetch_array();
            $this->insert(array(
                                "uname" => $username,
                                "passwd" => $result[$this->column_passwd],
                                "ip" => $result[$this->column_ip]
                            ));
            return $this->sync($username);
        }

        if (!($exist_in_bs_table || $exist_in_discuz_table))
            return false;

        if ($exist_in_bs_table && $exist_in_discuz_table) {
            $passwd1 = $this->select('username', $username)['password'];
            $passwd2 = $this->query("SELECT * FROM ".$this->table_name."
                WHERE ".$this->column_uname."='$username'")->fetch_array()[$this->column_passwd];
            if ($passwd1 == $passwd2) {
                return true;
            } else {
                // sync password
                $this->update($username, 'password', $passwd2);
                return $this->sync($username);
            }
        }

    }

}
