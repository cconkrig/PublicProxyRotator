# Public Proxy Rotator

This application scrapes ProxyNova to return a randomized public proxy server that has 100% up-time. This is useful when sites like api.weather.gov incorrectly cache resposnses without honoring the Cache-Control header parameter used by curl/wget.  This script gets around this by allowing you to use proxy servers to return data correctly.


### Prerequisites

PHP5+

## Built With

* [php-simple-html-dom-parser](https://sourceforge.net/projects/simplehtmldom/files/)

## Authors

* **Chris Conkright** - *Initial work* - [cconkrig](https://github.com/cconkrig)

## License

This project is licensed under the BSD 3 License - see the [LICENSE.md](LICENSE) file for details