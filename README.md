# Custom Animated 404 Page

A modern, animated 404 error page for portfolio projects built with Laravel and Tailwind CSS.

## Features

- Full-screen background video (`public/videos/404-bg.mp4`)
- Clean white/light background around video with rounded corners
- "Actor" font from Google Fonts
- Staggered fade-in entrance animation
- White "Back to Home" button with hover effects
- Responsive design (desktop & mobile)
- Respects `prefers-reduced-motion`
- No white overlay — video plays with original colors

## Project Structure

```
resources/
  views/
    errors/
      404.blade.php          # Custom 404 error page
public/
  videos/
    404-bg.mp4               # Background video (add your own)
routes/
  web.php                    # Contains 'home' route used by the 404 page
```

## Setup

1. Clone the repo:
   ```bash
   git clone https://github.com/refiepratama-dev/404-page.git
   cd 404-page
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Copy environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate app key:
   ```bash
   php artisan key:generate
   ```

5. Add your background video:
   - Place `404-bg.mp4` in `public/videos/`
   - Optional: place `404-poster.jpg` in `public/videos/`

6. Start the server:
   ```bash
   php artisan serve
   ```

7. Visit: `http://127.0.0.1:8000/this-page-does-not-exist`

## Customization

Edit `resources/views/errors/404.blade.php` to adjust:
- Video path (`asset('videos/404-bg.mp4')`)
- Typography (currently using `Actor` font)
- Button text and styling
- Padding and spacing values

## License

MIT License.