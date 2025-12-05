<?php

namespace Solivellaluisaberto\PayKit\Enums;

/**
 * Enum de monedas soportadas con sus códigos ISO 4217 y códigos numéricos de Redsys
 */
enum Currency: string
{
    case EUR = 'EUR'; // Euro
    case USD = 'USD'; // Dólar estadounidense
    case GBP = 'GBP'; // Libra esterlina
    case JPY = 'JPY'; // Yen japonés
    case CNY = 'CNY'; // Yuan chino
    case CHF = 'CHF'; // Franco suizo
    case CAD = 'CAD'; // Dólar canadiense
    case AUD = 'AUD'; // Dólar australiano
    case NZD = 'NZD'; // Dólar neozelandés
    case SEK = 'SEK'; // Corona sueca
    case NOK = 'NOK'; // Corona noruega
    case DKK = 'DKK'; // Corona danesa
    case PLN = 'PLN'; // Złoty polaco
    case CZK = 'CZK'; // Corona checa
    case HUF = 'HUF'; // Forinto húngaro
    case RUB = 'RUB'; // Rublo ruso
    case BRL = 'BRL'; // Real brasileño
    case MXN = 'MXN'; // Peso mexicano
    case ARS = 'ARS'; // Peso argentino
    case CLP = 'CLP'; // Peso chileno
    case COP = 'COP'; // Peso colombiano
    case PEN = 'PEN'; // Sol peruano
    case TRY = 'TRY'; // Lira turca
    case ZAR = 'ZAR'; // Rand sudafricano
    case INR = 'INR'; // Rupia india
    case KRW = 'KRW'; // Won surcoreano
    case SGD = 'SGD'; // Dólar de Singapur
    case HKD = 'HKD'; // Dólar de Hong Kong
    case THB = 'THB'; // Baht tailandés
    case MYR = 'MYR'; // Ringgit malayo
    case PHP = 'PHP'; // Peso filipino
    case IDR = 'IDR'; // Rupia indonesia
    case AED = 'AED'; // Dirham de los Emiratos Árabes Unidos
    case SAR = 'SAR'; // Riyal saudí
    case ILS = 'ILS'; // Shekel israelí
    case EGP = 'EGP'; // Libra egipcia

    /**
     * Obtener el código numérico ISO 4217 para esta moneda
     *
     * @return string Código numérico ISO 4217 de la moneda
     */
    public function getISO4217(): string
    {
        return match ($this) {
            self::EUR => '978',
            self::USD => '840',
            self::GBP => '826',
            self::JPY => '392',
            self::CNY => '156',
            self::CHF => '756',
            self::CAD => '124',
            self::AUD => '036',
            self::NZD => '554',
            self::SEK => '752',
            self::NOK => '578',
            self::DKK => '208',
            self::PLN => '985',
            self::CZK => '203',
            self::HUF => '348',
            self::RUB => '643',
            self::BRL => '986',
            self::MXN => '484',
            self::ARS => '032',
            self::CLP => '152',
            self::COP => '170',
            self::PEN => '604',
            self::TRY => '949',
            self::ZAR => '710',
            self::INR => '356',
            self::KRW => '410',
            self::SGD => '702',
            self::HKD => '344',
            self::THB => '764',
            self::MYR => '458',
            self::PHP => '608',
            self::IDR => '360',
            self::AED => '784',
            self::SAR => '682',
            self::ILS => '376',
            self::EGP => '818',
        };
    }

    /**
     * Obtener el nombre descriptivo de la moneda
     *
     * @return string Nombre de la moneda
     */
    public function getName(): string
    {
        return match ($this) {
            self::EUR => 'Euro',
            self::USD => 'Dólar estadounidense',
            self::GBP => 'Libra esterlina',
            self::JPY => 'Yen japonés',
            self::CNY => 'Yuan chino',
            self::CHF => 'Franco suizo',
            self::CAD => 'Dólar canadiense',
            self::AUD => 'Dólar australiano',
            self::NZD => 'Dólar neozelandés',
            self::SEK => 'Corona sueca',
            self::NOK => 'Corona noruega',
            self::DKK => 'Corona danesa',
            self::PLN => 'Złoty polaco',
            self::CZK => 'Corona checa',
            self::HUF => 'Forinto húngaro',
            self::RUB => 'Rublo ruso',
            self::BRL => 'Real brasileño',
            self::MXN => 'Peso mexicano',
            self::ARS => 'Peso argentino',
            self::CLP => 'Peso chileno',
            self::COP => 'Peso colombiano',
            self::PEN => 'Sol peruano',
            self::TRY => 'Lira turca',
            self::ZAR => 'Rand sudafricano',
            self::INR => 'Rupia india',
            self::KRW => 'Won surcoreano',
            self::SGD => 'Dólar de Singapur',
            self::HKD => 'Dólar de Hong Kong',
            self::THB => 'Baht tailandés',
            self::MYR => 'Ringgit malayo',
            self::PHP => 'Peso filipino',
            self::IDR => 'Rupia indonesia',
            self::AED => 'Dirham de los Emiratos Árabes Unidos',
            self::SAR => 'Riyal saudí',
            self::ILS => 'Shekel israelí',
            self::EGP => 'Libra egipcia',
        };
    }

    /**
     * Intentar crear una instancia del enum desde un string
     * Útil para convertir strings a enum de forma segura
     *
     * @param string $currency Código de moneda ISO 4217
     * @return self|null Retorna el enum o null si no existe
     */
    public static function tryFromString(string $currency): ?self
    {
        $currency = strtoupper($currency);
        
        try {
            return self::from($currency);
        } catch (\ValueError $e) {
            return null;
        }
    }
}

