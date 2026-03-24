<?php
function db(){static $pdo=null;if($pdo===null){$pdo=new PDO(DB_DSN,DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}return $pdo;}
function db_query($sql,$p=[]){$st=db()->prepare($sql);$st->execute($p);return $st;}

function db_execute($sql,$p=[]){$st=db()->prepare($sql);$st->execute($p);return $st->rowCount();}
function db_fetch_one($sql,$p=[]){$r=db_query($sql,$p)->fetch();return $r?:null;}
function db_fetch_all($sql,$p=[]){return db_query($sql,$p)->fetchAll();}
function db_fetch_value($sql,$p=[]){return db_query($sql,$p)->fetchColumn();}
