/**
 * Adds a file picker above the ARC copy markdown textarea so a .md file can
 * be chosen instead of pasted. The file is read in the browser (FileReader)
 * and its contents are pushed into the setting — nothing is uploaded to the
 * Media Library, and the textarea stays editable before saving.
 */
( function () {
	wp.customize.control( 'lienzoastra_arc_tpl_copy_md', function ( control ) {
		var $textarea = control.container.find( 'textarea' );
		if ( ! $textarea.length ) {
			return;
		}

		var input = document.createElement( 'input' );
		input.type = 'file';
		input.accept = '.md,.markdown,text/plain,text/markdown';
		input.style.marginBottom = '8px';
		input.style.width = '100%';

		$textarea.before( input );

		input.addEventListener( 'change', function ( event ) {
			var file = event.target.files && event.target.files[ 0 ];
			if ( ! file ) {
				return;
			}
			var reader = new FileReader();
			reader.onload = function () {
				wp.customize( 'lienzoastra_arc_tpl_copy_md' ).set( String( reader.result || '' ) );
			};
			reader.readAsText( file );
		} );
	} );
}() );
