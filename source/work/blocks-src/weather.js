// Weather Blocks - Gutenberg Editor
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

// Current Weather Block
registerBlockType('sgup/weather-current', {
    title: 'Current Weather',
    icon: 'cloud',
    category: 'sgup_weather',
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps({
            style: {
                ...attributes.style
            }
        });

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
                        <ToggleControl
                            label="Show Details"
                            checked={attributes.showDetails}
                            onChange={(value) => setAttributes({ showDetails: value })}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#e8f4fd', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>
                            ☀️ {attributes.title} <small>({attributes.showTitle ? 'Shown' : 'Hidden'})</small>
                        </h3>
                        <p style={{ margin: 0, color: '#666' }}>Current weather conditions will display here.</p>
                        {attributes.showLocationPicker && (
                            <p style={{ margin: '5px 0', fontSize: '12px', color: '#888' }}>
                                <em>Location picker enabled</em>
                            </p>
                        )}
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});

// Daily Forecast Block
registerBlockType('sgup/weather-daily', {
    title: 'Daily Forecast',
    icon: 'calendar',
    category: 'sgup_weather',

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
                        <ToggleControl
                            label="Show Hourly Breakdown"
                            checked={attributes.showHourly}
                            onChange={(value) => setAttributes({ showHourly: value })}
                        />
                        {attributes.showHourly && (
                            <RangeControl
                                label="Hours to Display"
                                value={attributes.hoursToShow}
                                onChange={(value) => setAttributes({ hoursToShow: value })}
                                min={6}
                                max={48}
                            />
                        )}
                        <ToggleControl
                            label="Include Today's Forecast"
                            checked={attributes.useNoaa}
                            onChange={(value) => setAttributes({ useNoaa: value })}
                            help="Include detailed text forecast"
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#fff8e6', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>
                            ☀️ {attributes.title} <small>({attributes.showTitle ? 'Shown' : 'Hidden'})</small>
                        </h3>
                        <p style={{ margin: 0, color: '#666' }}>Daily forecast will display here.</p>
                        {attributes.showHourly && (
                            <p style={{ margin: '5px 0', fontSize: '12px', color: '#888' }}>
                                <em>Showing {attributes.hoursToShow} hours</em>
                            </p>
                        )}
                        {attributes.useNoaa && (
                            <p style={{ margin: '5px 0', fontSize: '12px', color: '#888' }}>
                                <em>Including today's forecast</em>
                            </p>
                        )}
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});

// Hourly Forecast
registerBlockType('sgup/weather-daily-hourly', {
    title: 'Today\'s Hourly Forecast',
    icon: 'calendar',
    category: 'sgup_weather',

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
                        <RangeControl
                            label="Hours to Show"
                            value={attributes.hoursToShow}
                            onChange={(value) => setAttributes({ hoursToShow: value })}
                            min={6}
                            max={48}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#e6f7e6', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>
                            ☀️ {attributes.title} <small>({attributes.showTitle ? 'Shown' : 'Hidden'})</small>
                        </h3>
                        <p style={{ margin: '5px 0', fontSize: '12px', color: '#888' }}>
                            <em>Showing {attributes.hoursToShow} hours</em>
                        </p>
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});

// Weekly Forecast Block
registerBlockType('sgup/weather-weekly', {
    title: 'Weekly Forecast',
    icon: 'calendar-alt',
    category: 'sgup_weather',

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
                        <RangeControl
                            label="Days to Display"
                            value={attributes.daysToShow}
                            onChange={(value) => setAttributes({ daysToShow: value })}
                            min={3}
                            max={8}
                        />
                        <ToggleControl
                            label="Include Extended Forecast"
                            checked={attributes.useNoaa}
                            onChange={(value) => setAttributes({ useNoaa: value })}
                            help="Include detailed text forecast"
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#e6f7e6', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>
                            ☀️ {attributes.title} <small>({attributes.showTitle ? 'Shown' : 'Hidden'})</small>
                        </h3>
                        <p style={{ margin: 0, color: '#666' }}>{attributes.daysToShow}-day forecast will display here.</p>
                        {attributes.useNoaa && (
                            <p style={{ margin: '5px 0', fontSize: '12px', color: '#888' }}>
                                <em>Including extended forecast</em>
                            </p>
                        )}
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});

// Weekly Extended Forecast Block
registerBlockType('sgup/weather-weekly-extended', {
    title: 'Weekly Extended Forecast',
    icon: 'calendar-alt',
    category: 'sgup_weather',

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
                        <RangeControl
                            label="Days to Display"
                            value={attributes.daysToShow}
                            onChange={(value) => setAttributes({ daysToShow: value })}
                            min={3}
                            max={8}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#e6f7e6', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>
                            ☀️ {attributes.title} <small>({attributes.showTitle ? 'Shown' : 'Hidden'})</small>
                        </h3>
                        <p style={{ margin: 0, color: '#666' }}>{attributes.daysToShow}-day forecast will display here.</p>
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});

// Weather Alerts Block
registerBlockType('sgup/weather-alerts', {
    title: 'Alerts',
    icon: 'warning',
    category: 'sgup_weather',

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
                        <RangeControl
                            label="Maximum Alerts"
                            value={attributes.maxAlerts}
                            onChange={(value) => setAttributes({ maxAlerts: value })}
                            min={1}
                            max={10}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#ffe6e6', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>
                            ☀️ {attributes.title} <small>({attributes.showTitle ? 'Shown' : 'Hidden'})</small>
                        </h3>
                        <p style={{ margin: 0, color: '#666' }}>Weather alerts will display here when active.</p>
                        <p style={{ margin: '5px 0', fontSize: '12px', color: '#888' }}>
                            <em>Showing up to {attributes.maxAlerts} alerts</em>
                        </p>
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});

// Full Weather Dashboard Block
registerBlockType('sgup/weather-full', {
    title: 'Dashboard',
    icon: 'admin-site-alt3',
    category: 'sgup_weather',

    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Dashboard Settings">
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
                    </PanelBody>
                    <PanelBody title="Components" initialOpen={true}>
                        <ToggleControl
                            label="Show Location Picker"
                            checked={attributes.showLocationPicker}
                            onChange={(value) => setAttributes({ showLocationPicker: value })}
                        />
                        <ToggleControl
                            label="Show Weather Alerts"
                            checked={attributes.showAlerts}
                            onChange={(value) => setAttributes({ showAlerts: value })}
                        />
                        <ToggleControl
                            label="Show Current Weather"
                            checked={attributes.showCurrent}
                            onChange={(value) => setAttributes({ showCurrent: value })}
                        />
                        <ToggleControl
                            label="Show Today's Forecast"
                            checked={attributes.showNoaa}
                            onChange={(value) => setAttributes({ showNoaa: value })}
                        />
                        <ToggleControl
                            label="Show Hourly Forecast"
                            checked={attributes.showHourly}
                            onChange={(value) => setAttributes({ showHourly: value })}
                        />
                        <ToggleControl
                            label="Show 7-Day Forecast"
                            checked={attributes.showDaily}
                            onChange={(value) => setAttributes({ showDaily: value })}
                        />
                        <ToggleControl
                            label="Show 7-Day Extended Forecast"
                            checked={attributes.showDailyExtended}
                            onChange={(value) => setAttributes({ showDailyExtended: value })}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#f0f0f0', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>
                            ☀️ {attributes.title} <small>({attributes.showTitle ? 'Shown' : 'Hidden'})</small>
                        </h3>
                        <p style={{ margin: 0, color: '#666' }}>Full weather dashboard will display here.</p>
                        <div style={{ marginTop: '10px', fontSize: '12px', color: '#888' }}>
                            <strong>Active Components:</strong>
                            <ul style={{ margin: '5px 0', paddingLeft: '20px' }}>
                                {attributes.showLocationPicker && <li>Location Picker</li>}
                                {attributes.showAlerts && <li>Weather Alerts</li>}
                                {attributes.showCurrent && <li>Current Weather</li>}
                                {attributes.showNoaa && <li>Today's Forecast</li>}
                                {attributes.showHourly && <li>Hourly Forecast</li>}
                                {attributes.showDaily && <li>7 Day Forecast</li>}
                                {attributes.showDailyExtended && <li>7 Day Extended Forecast</li>}
                            </ul>
                        </div>
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});

// Location Picker Block
registerBlockType('sgup/weather-location', {
    title: 'Location Picker',
    icon: 'location',
    category: 'sgup_weather',

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
                            label="Compact Mode"
                            checked={attributes.compact}
                            onChange={(value) => setAttributes({ compact: value })}
                            help="Show a smaller, inline version"
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#f5f5f5', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>
                            ☀️ {attributes.title} <small>({attributes.showTitle ? 'Shown' : 'Hidden'})</small>
                        </h3>
                        <p style={{ margin: 0, color: '#666' }}>Location picker will display here.</p>
                        {attributes.compact && (
                            <p style={{ margin: '5px 0', fontSize: '12px', color: '#888' }}>
                                <em>Compact mode enabled</em>
                            </p>
                        )}
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});

// Weather Map Block
registerBlockType('sgup/weather-map', {
    title: 'Weather Map',
    icon: 'location-alt',
    category: 'sgup_weather',

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
                        <SelectControl
                            label="Map Layer"
                            value={attributes.mapLayer}
                            options={[
                                { label: 'Clouds', value: 'clouds' },
                                { label: 'Wind', value: 'wind' },
                                { label: 'Rain', value: 'rain' },
                                { label: 'Radar', value: 'radar' },
                                { label: 'Temperature', value: 'temp' },
                                { label: 'Humidity', value: 'rh' },
                                { label: 'Satelite', value: 'satelite' },
                                { label: 'Visibility', value: 'visibility' }                                
                            ]}
                            onChange={(value) => setAttributes({ mapLayer: value })}
                        />
                        <RangeControl
                            label="Map Height (px)"
                            value={attributes.maxHeight}
                            onChange={(value) => setAttributes({ maxHeight: value })}
                            min={200}
                            max={800}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#e0f2fe', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>
                            🗺️ {attributes.title} <small>({attributes.showTitle ? 'Shown' : 'Hidden'})</small>
                        </h3>
                        <p style={{ margin: 0, color: '#666' }}>Weather map will display here.</p>
                        <p style={{ margin: '5px 0', fontSize: '12px', color: '#888' }}>
                            <em>Layer: {attributes.mapLayer} | Height: {attributes.maxHeight}px</em>
                        </p>
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});