export const __ = ( value ) => value;
export const _n = ( singular, plural, num ) => num <= 1 ? singular : plural;
export const sprintf = ( text, replacement ) => text.replace( /%d|%s/, replacement );
// English plural rule: only 1 takes the singular, so 0 reads "0 minutes" as it does with real translations.
export const _nx = ( singular, plural, num ) => ( 1 === num ? singular : plural );
