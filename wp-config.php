<?php
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе
 * установки. Необязательно использовать веб-интерфейс, можно
 * скопировать файл в "wp-config.php" и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки MySQL
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** Параметры MySQL: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define( 'DB_NAME', 'solvetdb' );

/** Имя пользователя MySQL */
define( 'DB_USER', 'root' );

/** Пароль к базе данных MySQL */
define( 'DB_PASSWORD', '' );

/** Имя сервера MySQL */
define( 'DB_HOST', 'localhost' );

/** Кодировка базы данных для создания таблиц. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Схема сопоставления. Не меняйте, если не уверены. */
define( 'DB_COLLATE', '' );

/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу.
 * Можно сгенерировать их с помощью {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными. Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '!MqI6J2:@2HDx,1}%TjAV({@x^b1P%%Tw>U[nZz4 pvX0{!eR2y7?s$~H~Nnx;3L' );
define( 'SECURE_AUTH_KEY',  ')w}#VptTb{5}cZj[68jQ^:O&)EO(_z|T[-tQ.6su`$?x[zE=]kR6A_k|1i3?^ZZ-' );
define( 'LOGGED_IN_KEY',    'y5!H9bv7_)7oBk9<f_WvK#9$+dyX$4Q49pFOST5044mmG_7&5h-3K&aOS}nd9?~O' );
define( 'NONCE_KEY',        'hzk)tE!tY?;B_mqW7[_IvV!;V?j(8j2qwbUn<5=wcPj*?7/jpg9J*5B-|T$pEk@t' );
define( 'AUTH_SALT',        '2l-H_e%},pazxz~?z}k/f&mlJGF$6tW)jt~+mZ`8*KH+{mM4[/U4I2d[+Cn5.5OX' );
define( 'SECURE_AUTH_SALT', '<S@NL.#E:FN*k2u6GDCz|APp13~h0> tf4:T0=N@1b%XVm)znyEpFw[0Cud/ vWl' );
define( 'LOGGED_IN_SALT',   'GlCw)k-C9^yBv)PiqH4qB@6En1^M`IADR4<M@p40-(jiFUjtc7b,4)31i2]MnA3Q' );
define( 'NONCE_SALT',       'r9k`,DM?8vh{TEh6 @@7FKNT2)D_?q`AaP1b1YV1fdhtB3j :h}sBy[Mi;6~}1E<' );

/**#@-*/

/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix = 'wp_';

/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 *
 * Информацию о других отладочных константах можно найти в Кодексе.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* Это всё, дальше не редактируем. Успехов! */

/** Абсолютный путь к директории WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Инициализирует переменные WordPress и подключает файлы. */
require_once( ABSPATH . 'wp-settings.php' );
