<?php
namespace App\Config;

class MainConfig
{
    /** у нас в БД может быть 100500 мильенов записей, да еще и под нагрузкой, поэтому обходить БД нужно по кускам размером ну пусть 1000 записей.
    если делать так: "select * from table" можно завесить sql-сервер, а заодно и сервер приложений, когда кончится память от миллиарда объектов
    поэтому данные из БД забираем частями. **/
    const CHUNKSIZE = 1000;

    // название таблицы в БД
    const TABLENAME = 'books_catalog';
    // это поля, где исбн может быть один
    const ISBNSINGLEFIELDS = ['isbn', 'isbn2', 'isbn3'];
    // а в этих несколько через разделитель
    const ISBNMULTIFIELDS = ['isbn4', 'isbn_wrong'];
    // корректные исбн поштучно сюда
    const CORRECTSINGLEISBLFIELDSTOINSERT = ['isbn2', 'isbn3'];
    // а корректные через разделитель сюда
    const CORRECTMULTIFIELDSTOINSERT = ['isbn4'];
    // некорректные сюда
    const WRONGMULTIFIELDSTOINSERT = ['isbn_wrong'];
    // разделители при парсинге исбн из таблицы
    const ISBNSDEVIDER = ', ';
    const ISBNDEVIDER2 = ',';
    // длины символов
    const ISBN10 = 10;
    const ISBN13 = 13;
    // расширение для файлов логов
    const LOGFILEEXT = 'csv';
    // папка, куда скрипт будет пробовать положить логи
    const LOGFILEPATH = 'logs';
    // формат даты в лог файле
    const DATETIMEFORMAT = 'Y-m-d H:i:s';




}