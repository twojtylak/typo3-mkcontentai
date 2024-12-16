CREATE TABLE tx_mkcontentai_domain_model_alt_text_logs
(
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    alternative text,
    table_name varchar(50) NOT NULL DEFAULT '',
    sys_file_metadata int(11) unsigned DEFAULT '0',
    PRIMARY KEY (uid)
);

CREATE TABLE sys_file_metadata
(
    alt_text_logs int(11) unsigned DEFAULT '0'
);
