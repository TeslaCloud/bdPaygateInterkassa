# [bd]Paygate: Interkassa
Данная модификация позволяет вам настроить приём платежей на своем форуме через платежный шлюз [Interkassa](https://www.interkassa.com/)

## Требования
Требуется аддон [bd] Paygates версии не ниже 1.5.2

## Установка и настройка

### Настройка магазина Interkassa

* Откройте вкладку "Безопастность"
 * Выберите алгоритм подписи "MD5"
 * Активируйте кнопку "Проверять подпись в форме запроса платежа"
![Image](https://matew.pw/screens/clip-2016-08-07-22-10-50-21160173.png)
* Откройте вкладку "Интерфейс". 
 * Укажите URL взаимодействия в формате 'http://domain.com/bdpaygate/callback.php?p=interkassa'. Тип запроса должен быть "POST". 
 * Далее нажмите "Дополнительно" и укажите в поле "Текст успешного ответа" значение "OK". 
* Все должено быть примерно так:
![Image](https://matew.pw/screens/clip-2016-08-07-22-16-15-86318491.png)

### Настройка XenForo

* Укажите в настройках данные, которые вы получили на странице своего магазина
Только первые два поля являются обязательными, остальное - опционально
![Image](https://matew.pw/screens/clip-2016-08-07-22-05-07-98682728.png)