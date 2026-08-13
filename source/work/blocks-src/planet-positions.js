import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

registerBlockType('sgup/planet-positions', {
    title: 'Planet Positions',
    icon: 'admin-site-alt3',
    category: 'sgup_space',

    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Settings">
                        <TextControl
                            label="Title"
                            value={attributes.title}
                            onChange={(value) => setAttributes({ title: value })}
                        />
                        <ToggleControl
                            label="Show Title"
                            checked={attributes.showTitle}
                            onChange={(value) => setAttributes({ showTitle: value })}
                        />
                        <ToggleControl
                            label="Show Location Picker"
                            checked={attributes.showLocationPicker}
                            onChange={(value) => setAttributes({ showLocationPicker: value })}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#1a1a2e', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0', color: '#f97316' }}>
                            🪐 {attributes.title}
                        </h3>
                        <p style={{ margin: 0, color: '#aaa' }}>Planet positions will display here.</p>
                        <p style={{ margin: '5px 0 0 0', fontSize: '11px', color: '#666' }}>
                            <em>Requires AstronomyAPI credentials</em>
                        </p>
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});