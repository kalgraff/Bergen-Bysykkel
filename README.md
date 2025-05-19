# Bergen Bysykkel WordPress Plugin

A WordPress plugin that displays real-time availability data for Bergen city bike stations. The plugin shows the number of available bikes and docks at Nykirken and St. Jakobs Plass stations, with color-coded status indicators.

## Features

- Displays real-time data from the official Bergen Bysykkel API
- Shows available bikes and docks for Nykirken and St. Jakobs Plass stations
- Color-coded status indicators (green, orange, red) based on availability
- Available as both a widget and a shortcode
- Responsive design that works on all devices
- All styles contained in a single file for easy customization

## Installation

1. Download the `bergen-bysykkel.zip` file
2. Log in to your WordPress admin panel
3. Go to Plugins → Add New → Upload Plugin
4. Upload the zip file and activate the plugin
5. The plugin is now ready to use

## Usage

### Widget

1. Go to Appearance → Widgets
2. Drag the "Bergen Bysykkel" widget to your desired widget area
3. Customize the widget title if needed
4. Save your changes

### Shortcode

Add the following shortcode to any page or post:

```
[bergen_bysykkel]
```

## Technical Details

- The plugin fetches data from the Bergen Bysykkel GBFS API
- Data is refreshed each time the page loads
- Color indicators change based on percentage of availability:
  - Green: More than 50% available
  - Orange: Between 20% and 50% available
  - Red: Less than 20% available

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Developer

Created by Ove G. Kalgraff

## License

This project is licensed under the MIT License - see the LICENSE file for details.
