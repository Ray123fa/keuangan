---
name: hyperui
description: Use when building with HyperUI — free open-source Tailwind CSS HTML components. Covers browsing, searching, and fetching components via MCP tools, adapting markup to projects, dark mode variants, category structure (application, marketing, neobrutalism), component customization, and @tailwindcss/forms plugin requirements.
---

# HyperUI Components

## 1. Overview

HyperUI is a free collection of **481 copy-paste HTML components** built with Tailwind CSS. Components are pure HTML — no JavaScript framework required. Three categories: application UI, marketing pages, and neobrutalism style.

All components are available through the `frontend-components` MCP server under the `hyperui` framework.

## 2. MCP Workflow

### 2.1 Discovering Components

```
# See all categories and component counts
list_frameworks → hyperui entry shows categories

# Browse a category
list_components(framework: "hyperui", category: "application")
list_components(framework: "hyperui", category: "marketing")
list_components(framework: "hyperui", category: "neobrutalism")
```

### 2.2 Searching Components

```
# Search by keyword across all HyperUI components
search_components(query: "badge", framework: "hyperui")
search_components(query: "modal dark", framework: "hyperui")
search_components(query: "pricing", framework: "hyperui")
```

### 2.3 Fetching Component Code

```
# By structured path
get_component(framework: "hyperui", category: "application", component_type: "badges", variant: "1")

# Dark variant
get_component(framework: "hyperui", category: "application", component_type: "badges", variant: "1-dark")

# By path from search results
get_component_by_path(path: "hyperui/application/badges/1.html")
```

## 3. Category Structure

### 3.1 Application UI (`application/`)

Components for building application interfaces:

| Type | Purpose |
|------|---------|
| accordions | Expandable content sections |
| badges | Status indicators, labels, tags |
| breadcrumbs | Navigation breadcrumb trails |
| button-groups | Grouped action buttons |
| checkboxes | Styled checkbox inputs |
| details-list | Key-value detail displays |
| dividers | Content section separators |
| dropdown | Dropdown menus and selects |
| empty-states | Empty/placeholder states |
| file-uploaders | File upload interfaces |
| filters | Filter bars and controls |
| grids | Grid layout patterns |
| inputs | Text input fields |
| loaders | Loading spinners and skeletons |
| media | Image and media containers |
| modals | Dialog/modal overlays |
| pagination | Page navigation controls |
| progress-bars | Progress indicators |
| quantity-inputs | Number increment/decrement |
| radio-groups | Radio button groups |
| selects | Custom select dropdowns |
| side-menu | Sidebar navigation menus |
| stats | Statistic display cards |
| steps | Step/wizard indicators |
| switches | Toggle switches |
| tables | Data tables |
| tabs | Tab navigation |
| tags | Tag/chip elements |
| textareas | Multi-line text inputs |
| toggles | Toggle buttons |
| tooltips | Hover tooltip elements |
| vertical-menu | Vertical navigation menus |

### 3.2 Marketing Pages (`marketing/`)

Components for landing pages and marketing sites:

| Type | Purpose |
|------|---------|
| alerts | Banner alerts and notices |
| announcements | Announcement bars |
| banners | Hero and promotional banners |
| blog-cards | Blog post card layouts |
| cards | Marketing content cards |
| ctas | Call-to-action sections |
| error-pages | 404 and error page layouts |
| faqs | FAQ accordion sections |
| footers | Page footer layouts |
| forms | Contact/signup forms |
| headers | Page header sections |
| pricing | Pricing table components |
| product-cards | Product showcase cards |
| product-collections | Product grid collections |
| sections | Content section layouts |
| showcases | Feature showcase sections |
| stats | Marketing statistics displays |
| testimonials | Testimonial/review cards |

### 3.3 Neobrutalism (`neobrutalism/`)

Bold, retro-styled components with thick borders and shadow offsets:

| Type | Purpose |
|------|---------|
| Various | Neobrutalism-styled versions of common UI patterns |

## 4. Dark Mode Variants

Most HyperUI components have a dark variant file alongside the light version:
- `1.html` — light/default version
- `1-dark.html` — dark mode version

### 4.1 Dark Variant Patterns

Dark variants use Tailwind's dark mode classes. When adapting to a project using `class` dark mode strategy:
- The dark variant code works when a parent element has `class="dark"`
- Merge light and dark into one component using `dark:` prefix classes

### 4.2 Merging Light and Dark

When the project needs both modes in one file, combine the classes:

```html
<!-- Light version has: bg-white text-gray-900 -->
<!-- Dark version has: bg-gray-900 text-white -->
<!-- Combined: -->
<div class="bg-white text-gray-900 dark:bg-gray-900 dark:text-white">
```

Fetch both variants to see the exact class differences, then merge.

## 5. Adapting Components

### 5.1 General Adaptation Rules

1. **Strip wrapper classes** — The HTML body wrapper uses `flex flex-wrap justify-center gap-4 p-6` for preview layout. Remove these and use your own container.
2. **Replace placeholder content** — Swap lorem ipsum, placeholder images, and dummy data with real content.
3. **Adjust colors** — Replace the default color palette (purple, indigo, blue) with your project's brand colors.
4. **Adjust sizing** — Components use specific sizes that may need adjustment for your layout.
5. **Add interactivity** — Components are static HTML. Add JavaScript behavior (click handlers, state management) as needed.

### 5.2 Common Modifications

| Task | Approach |
|------|----------|
| Change colors | Find-replace color names (e.g., `purple-600` → `blue-600`) |
| Make responsive | Components include responsive classes but verify breakpoint behavior |
| Add hover states | Most components include `hover:` variants; add where missing |
| Customize spacing | Adjust `p-`, `m-`, `gap-` values to match your design system |
| Integrate with framework | Wrap in React/Vue components, replace static elements with dynamic data |

### 5.3 Form Components

Many application UI form components require the `@tailwindcss/forms` plugin:

```bash
npm install @tailwindcss/forms
```

```js
// tailwind.config.js (v3)
plugins: [require('@tailwindcss/forms')]

// v4 CSS — automatic if using the plugin
@import "tailwindcss";
@plugin "@tailwindcss/forms";
```

Components needing this plugin: inputs, selects, checkboxes, radio-groups, textareas, switches, toggles, file-uploaders.

## 6. Integration Patterns

### 6.1 With React

```jsx
function Badge({ children, color = "purple" }) {
  // Fetch hyperui/application/badges/1 as base
  return (
    <span className={`rounded-full bg-${color}-100 px-2.5 py-0.5 text-sm text-${color}-700`}>
      {children}
    </span>
  );
}
```

### 6.2 With Vue

```vue
<template>
  <!-- Adapted from hyperui/application/badges/1 -->
  <span :class="`rounded-full bg-${color}-100 px-2.5 py-0.5 text-sm text-${color}-700`">
    <slot />
  </span>
</template>

<script setup>
defineProps({ color: { type: String, default: 'purple' } })
</script>
```

### 6.3 With Alpine.js

HyperUI components pair well with Alpine.js for interactivity:

```html
<!-- Dropdown from hyperui/application/dropdown with Alpine.js toggle -->
<div x-data="{ open: false }">
  <button @click="open = !open">Menu</button>
  <div x-show="open" @click.away="open = false">
    <!-- dropdown content from component -->
  </div>
</div>
```

## 7. Component Selection Strategy

1. **Start with search** — Use `search_components` with descriptive keywords
2. **Browse by category** — Use `list_components` to explore what's available in a category
3. **Check variants** — Many component types have 5-10+ variants; review several before picking
4. **Check dark mode** — If the project needs dark mode, fetch the `-dark` variant too
5. **Combine variants** — Mix elements from different variants for custom designs
6. **Prefer simpler variants** — Start with variant `1` (simplest) and add complexity as needed

## 8. Workflow Summary

| Step | Action |
|------|--------|
| 1. Identify need | Determine what UI component is needed |
| 2. Search | `search_components(query: "keyword", framework: "hyperui")` |
| 3. Browse | `list_components(framework: "hyperui", category: "application")` |
| 4. Fetch | `get_component(framework: "hyperui", ...)` |
| 5. Fetch dark | Get the `-dark` variant if needed |
| 6. Adapt | Strip wrapper, replace content, adjust colors |
| 7. Integrate | Wrap in framework component, add interactivity |
