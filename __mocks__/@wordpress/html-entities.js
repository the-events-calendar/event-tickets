export const decodeEntities = ( text ) => text.replace( /&#8217;/g, '’' ).replace( /&amp;/g, '&' );
