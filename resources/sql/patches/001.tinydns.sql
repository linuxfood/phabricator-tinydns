CREATE DATABASE /*!32312 IF NOT EXISTS*/ `{$NAMESPACE}_tinydns` /*!40100 DEFAULT CHARACTER SET {$CHARSET} COLLATE {$COLLATE_TEXT} */;

USE `{$NAMESPACE}_tinydns`;

CREATE TABLE /*!32312 IF NOT EXISTS*/ `domain` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `phid` varbinary(64) NOT NULL,
      `domainRoot` varchar(128) COLLATE {$COLLATE_TEXT} NOT NULL,
      `ttl` int(10) unsigned NOT NULL,
      `defaultRecordTTL` int(10) unsigned NOT NULL,
      `ns1` varbinary(128) NOT NULL,
      `ns2` varbinary(128) NOT NULL,
      `viewPolicy` varbinary(64) NOT NULL,
      `editPolicy` varbinary(64) NOT NULL,
      `dateCreated` int(10) unsigned NOT NULL,
      `dateModified` int(10) unsigned NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `phid`  (`phid`),
      UNIQUE KEY `domainRoot` (`domainRoot`)
) ENGINE=InnoDB DEFAULT CHARSET={$CHARSET} COLLATE={$COLLATE_TEXT};

CREATE TABLE /*!32312 IF NOT EXISTS*/ `record` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `phid` varbinary(64) NOT NULL,
    `domainPHID` varbinary(64) NOT NULL,
    `recordType` varbinary(4) NOT NULL,
    `ttl` int(10) unsigned NOT NULL,
    `fqdn` varchar(255) COLLATE {$COLLATE_TEXT} NOT NULL,
    `data` longblob NOT NULL,
    `data2` longblob,
    `dateCreated` int(10) unsigned NOT NULL,
    `dateModified` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fqdn`  (`fqdn`),
    KEY `domainPHID` (`domainPHID`)
) ENGINE=InnoDB DEFAULT CHARSET={$CHARSET} COLLATE={$COLLATE_TEXT};

