( function ( wp, config ) {
	if ( ! wp || ! config ) {
		return;
	}

	const el = wp.element.createElement;
	const __ = wp.i18n.__;
	const metaKey = config.metaKey;
	const themeOptions = Object.keys( config.themes || {} ).map( function ( stylesheet ) {
		return {
			label: config.themes[ stylesheet ],
			value: stylesheet,
		};
	} );

	themeOptions.unshift( {
		label: __( 'Inherit default', 'tempered-themechanger' ),
		value: '',
	} );

	function ThemeChangerPanel() {
		const meta = wp.data.useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );

		const editPost = wp.data.useDispatch( 'core/editor' ).editPost;
		const selectedTheme = meta[ metaKey ] || '';

		return el(
			wp.editPost.PluginDocumentSettingPanel,
			{
				name: 'tempered-themechanger',
				title: __( 'Theme Changer', 'tempered-themechanger' ),
			},
			el( wp.components.SelectControl, {
				label: __( 'Theme', 'tempered-themechanger' ),
				help: __( 'Save or preview to load the selected theme.', 'tempered-themechanger' ),
				value: selectedTheme,
				options: themeOptions,
				onChange: function ( nextTheme ) {
					editPost( {
						meta: {
							...meta,
							[ metaKey ]: nextTheme,
						},
					} );
				},
			} )
		);
	}

	wp.plugins.registerPlugin( 'tempered-themechanger', {
		render: ThemeChangerPanel,
	} );
}( window.wp, window.temperedThemeChanger ) );
