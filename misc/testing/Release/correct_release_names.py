#!/usr/bin/python
import os
import re
import cymysql as mdb
import sets

print __name__

#nzbFileRegex = re.compile(r'^([0-9]|[a-f])+\.nzb\.gz$')

#Release Name: Big.Brother

def main():
    with open ("../../../nzedb/config/config.php", "r") as confFile:
        config = confFile.read().replace('\n', '')
    m = re.search(r"define\('DB_NAME'\W*,\W*'(\w+)", config)
    dbName = m.groups()[0]
    dbHost = re.search(r"define\('DB_HOST'\W*,\W*'(\w+)", config).groups()[0]
    dbUser = re.search(r"define\('DB_USER'\W*,\W*'(\w+)", config).groups()[0]
    dbPw = re.search(r"define\('DB_PASSWORD'\W*,\W*'(\w+)", config).groups()[0]

    con = mdb.connect(host=dbHost, user=dbUser, passwd=dbPw, db=dbName)
    cur = con.cursor()

    #update releases set searchname = regexp_replace(searchname, '^Title\\W+: ', '') where searchname like ('Title=%');
    toFixList = [('Title=%', r'^Title\W+: '), 
    ('TiTLE > %', r'^TiTLE \W+'), 
    ('Title > %', r'^Title \W+'), 
    ('Name....%', r'^(?i)Name\W+'), 
    ('Release Name: %', r'^Release Name\W+'),
    ('R.E.L.E.A.S.E =%', r'^R\.E\.L\.E\.A\.S\.E \W+'),
    ('RELEASE.NAME%', r'^RELEASE\.NAME\W+'),
    ('RELEASE.%', r'^(?i)RELEASE\.\W+'),
    ('[color=#%', r'\[color=#.*?\[/color\]\W*'),
    ('[color=%', r'^\[color=\w+\]'),
    ]
    for toFix in toFixList:
        cur.execute("select id, searchname from releases where searchname like '%s'" % (toFix[0]));
        fetchsize = 1000
        results = cur.fetchmany(fetchsize);
        while results:
            for result in results:
                id = result[0]
                searchname = result[1]
                newsearchname = re.sub(toFix[1], '', searchname)
                print searchname, newsearchname
#                newsearchname = repr(newsearchname)
                cur.execute("update releases set searchname = %s where id = %s", (newsearchname, id))
            results = cur.fetchmany(fetchsize);

if __name__ == "__main__":
    main()

