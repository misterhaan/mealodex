create table if not exists unit (
	id tinyint unsigned not null primary key auto_increment,
	measure tinyint unsigned not null default 0 comment 'Units can only be converted among the same measure.  Zero for no conversion, 1-volume, 2-weight',
	abbr varchar(8) not null default '' comment 'Displayed for ingredient amounts.  If bkank, name is used.',
	unique(measure, abbr),
	name varchar(24) not null comment 'Longer name used as tooltip for abbreviation',
	unique(measure, name),
	factor int unsigned not null comment 'Used to convert between units of the same measure'
);
