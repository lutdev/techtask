# Task description
Использовать:
PHP 5.6+
Исключительно без фреймворков, можно использовать отдельные библиотеки
(через composer).

- реализовать авторизацию на сайте http://forumodua.com/
- реализовать парсинг одной страницы темы с разбором всех сообщений
- сохранить каждый пост в отдельном файле (название уникальное в виде 
тема + дата)
- пост должен быть разобран по строкам на составляющие: заголовок, 
автор, дата, сам текст

Логин-пароль от сайта и адрес темы, как и адрес форм авторизации и т.п.
должны быть вынесены в отдельный файл конфигурации.

# About
All configuration is in the `.env` file. All parsed posts will be in the `posts` directory.

# Requirements
1. PHP 7.1+

# Run parser
1. checkout project via `GIT`
```
cd /path/to/project
git clone https://github.com/lutdev/techtask.git
```
2. Create `.env` file
```
rsync env.example .env
```
3. Run
```
php index.php
```

# Versions
Current version is `0.4` (master branch)

## v0.1
Create simple parser

## v0.2
Create authorisation

## v0.3
Init configuration

## v0.4
Init composer, autoloader and write classes.
