# Производственный календарь в формате JSON.

Файл:
[calendar.json](../blob/master/calendar.json)

Красивая версия:
[calendar-pretty.json](../blob/master/calendar-pretty.json)

В формате basicdata.ru:
[calendar-basicdata.json](../blob/master/calendar-basicdata.json)

Источник данных: http://data.gov.ru/opendata/7708660670-proizvcalendar

Статус дня:
* 0 (не указан): рабочий день
* 1: сокращённый предпраздничный день
* 2: выходной день
* 6: перенесённый выходной день (по-умолчанию этот статус отключен)

## Требования

* make
* curl
* php-cli
* php-json

## Использование

`make all`

Будут созданы файлы `calendar.json`, `calendar-pretty.json` и `calendar-basicdata.json`.

