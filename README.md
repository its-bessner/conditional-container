# ItsBessner Conditional Container Bundle

![License](https://img.shields.io/badge/license-MIT-green)  
Custom **Contao CMS** bundle that provides a conditional container element to display content based on specific events or conditions.

---

## Features

- **Conditional Content Display**: Allows content to be displayed before or on specific occasions.
- **Day Offset**: Configure how many days before the event the content should be visible.
- **Customizable Settings**: Easily define conditions and display logic.

---

## Installation

1. Install the bundle via Composer:
   ```bash
   composer require itsbessner/conditional-container-bundle
   ```

2. Add the bundle to your **Contao CMS** project if not automatically registered.

3. Perform the database update through Contao's Install Tool to apply the necessary changes.

---

## Configuration

The following settings and translations are included in the bundle:

### Content Element Translations (Example)

**`CTE` Definition:**

```php
$GLOBALS['TL_LANG']['CTE']['itsbessner_conditional_container'] = [
    'Conditional Container',
    "Contains events before or at which the content should be displayed."
];
```

### Backend Field Translations

**Selection Field:**

```php
$GLOBALS['TL_LANG']['tl_content']['itsbessner_conditional_selection'] = [
    'Condition - Selection',
    "Specify here on which events the content should be displayed."
];
```

**Days Offset:**

```php
$GLOBALS['TL_LANG']['tl_content']['itsbessner_conditional_days_before'] = [
    "Lead time in days",
    "How many days before the respective event should the content be displayed?"
];
```

---

## Usage

1. Add the **Conditional Container** content element in the Contao CMS backend.
2. Configure the following:
   - **Condition - Selection**: Select the events or conditions for displaying content.
   - **Lead Time in Days**: Define how many days before the event the content should be visible.
3. Save and publish the content element.

Your content will now be shown based on the specified conditions.

---

## License

This bundle is licensed under the [MIT License](LICENSE).  
Feel free to use, modify, and distribute it as needed.

---

## Contributing

We welcome contributions to improve this bundle.  
To contribute, please fork the repository, make your changes, and submit a pull request.

---

## Support

If you encounter issues or have questions, feel free to open an issue on GitHub or reach out to us.