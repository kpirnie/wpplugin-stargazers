import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';

registerBlockType('sgup/cme-alerts', {
    title: 'CME Alerts',
    icon: 'warning',
    category: 'sgup_space',

    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Pagination Settings">
                        <ToggleControl
                            label="Show Pagination"
                            checked={attributes.showPaging}
                            onChange={(value) => setAttributes({ showPaging: value })}
                        />
                        {attributes.showPaging && (
                            <>
                                <SelectControl
                                    label="Paging Location"
                                    value={attributes.pagingLocation}
                                    options={[
                                        { label: 'Top', value: 'top' },
                                        { label: 'Bottom', value: 'bottom' },
                                        { label: 'Both', value: 'both' }
                                    ]}
                                    onChange={(value) => setAttributes({ pagingLocation: value })}
                                />
                                <RangeControl
                                    label="Items Per Page"
                                    value={attributes.perPage}
                                    onChange={(value) => setAttributes({ perPage: value })}
                                    min={1}
                                    max={20}
                                />
                            </>
                        )}
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#f5f5f5', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>☀️ CME Alerts</h3>
                        {attributes.showPaging && (
                            <p style={{ margin: '5px 0', fontSize: '12px', color: '#666' }}>
                                <em>Pagination: {attributes.pagingLocation} ({attributes.perPage} per page)</em>
                            </p>
                        )}
                        <p style={{ margin: 0, color: '#666' }}>CME alerts will display here on the front-end.</p>
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});