calendar.json: calendar.csv csv2json.php
	php csv2json.php $< > $@

calendar-pretty.json: calendar.csv csv2json.php
	php csv2json.php --pretty $< > $@

calendar-basicdata.json: calendar.csv csv2json.php
	php csv2json.php --basicdata-format $< > $@

calendar.csv: latest-csv-url.txt
	curl -s -o $@ $(shell cat $<)

latest-csv-url.txt: Makefile
	curl -s -o .page.html http://data.gov.ru/opendata/7708660670-proizvcalendar
	grep -Eo '/data-[0-9Ta-z-]+\.csv' .page.html | sort -r | head -1 > .timestamp
	grep -o "http[0-9a-z:/\.-]*$$(cat .timestamp)" .page.html | head -1 > latest-csv-url.txt
	rm -f .timestamp .page.html

clean:
	rm -f calendar* latest-csv-url.txt .page.html .timestamp

all: calendar.json calendar-pretty.json calendar-basicdata.json
