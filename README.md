# 🔍 Laravel Livewire Select2 Component

[![Latest Version on Packagist](https://img.shields.io/packagist/v/afantecsf/livewire-select2.svg?style=flat-square)](https://packagist.org/packages/afantecsf/livewire-select2)
[![Total Downloads](https://img.shields.io/packagist/dt/afantecsf/livewire-select2.svg?style=flat-square)](https://packagist.org/packages/afantecsf/livewire-select2)
[![Tests](https://github.com/afantecsf/livewire-select2/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/afantecsf/livewire-select2/actions/workflows/run-tests.yml)
[![License](https://img.shields.io/packagist/l/afantecsf/livewire-select2.svg?style=flat-square)](https://packagist.org/packages/afantecsf/livewire-select2)

A powerful, flexible Select2-like dropdown component for Laravel Livewire with Bootstrap 5 styling. Features include dynamic searching, dependent selects, API integration, and more.

## 🚀 Features

- 🔎 Dynamic search with debouncing
- 📱 Mobile-friendly and responsive
- 🔄 Dependent/cascading selects
- 🎨 Bootstrap 5 styling
- 🌐 API integration support
- 🔒 SQL injection prevention
- 🎯 Custom filtering options
- ⚡ Real-time updates
- 🧪 Comprehensive test coverage

## 📦 Installation

You can install the package via composer:

```bash
composer require afantecsf/livewire-select2
```

## 🛠️ Basic Usage

```php
<livewire:select2 
    name="category_id"
    model="App\Models\Category"
    placeholder="Select a category"
    wire:model="selectedCategory"
/>
```

## 🎯 Advanced Usage

### Static Options
```php
<livewire:select2 
    name="status"
    :static-options="[
        ['id' => 1, 'name' => 'Active'],
        ['id' => 2, 'name' => 'Inactive']
    ]"
/>
```

### Dependent Selects
```php
<div>
    <!-- Parent select -->
    <livewire:select2 
        name="country_id"
        id="country"
        model="App\Models\Country"
        placeholder="Select country"
        wire:model="selectedCountry"
    />

    <!-- Child select -->
    <livewire:select2 
        name="city_id"
        id="city"
        model="App\Models\City"
        placeholder="Select city"
        depends-on="country"
        wire:model="selectedCity"
        :additional-params="['country_id' => $selectedCountry]"
    />
</div>
```

### API Integration
```php
<livewire:select2 
    name="user"
    api-endpoint="/api/users/search"
    :debounce="500"
    :min-input-length="3"
/>
```

### Custom Filtering
```php
<livewire:select2 
    name="product"
    model="App\Models\Product"
    :additional-params="[
        'category_id' => 5,
        'in_stock' => true
    ]"
    filter-callback="activeProducts"
/>
```

## ⚙️ Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| name | string | required | Input name |
| id | string | name value | Component ID |
| placeholder | string | 'Select option' | Placeholder text |
| model | string | null | Laravel model class |
| apiEndpoint | string | null | API endpoint for remote data |
| staticOptions | array | [] | Array of static options |
| dependsOn | string | '' | ID of parent select |
| displayField | string | 'name' | Field to display |
| valueField | string | 'id' | Field to use as value |
| minInputLength | int | 2 | Min chars for search |
| debounce | int | 300 | Debounce delay in ms |
| filterCallback | string | null | Custom filter method |
| additionalParams | array | [] | Additional parameters |

## 🧪 Testing

```bash
composer test
```

## 🤝 Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📝 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## ✨ Credits

- [Agustin Martin](https://github.com/jekhar)
- [All Contributors](../../contributors)

## 🏗️ About

This package is actively maintained. For support:
- 🐛 [Report a bug](https://github.com/afantecsf/livewire-select2/issues)
- 💡 [Request a feature](https://github.com/afantecsf/livewire-select2/issues)
- 💬 [Ask a question](https://github.com/afantecsf/livewire-select2/discussions)