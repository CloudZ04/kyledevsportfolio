# Deploying this site to Vercel

## What was changed for Vercel

- **PHP lives in `api/`**  
  Vercel only runs PHP as serverless functions, and those must be in the `api/` folder. The contact form handler is now at **`api/contact.php`** (the old root `contact.php` still works locally with XAMPP).

- **`vercel.json`**  
  - Tells Vercel to use the PHP runtime for `api/*.php`.  
  - Runs `composer install --no-dev` during build so `vendor/` (PHPMailer) is available.  
  - Rewrites `/contact.php` to `/api/contact.php` so your existing `fetch('contact.php')` in `script.js` works on Vercel without changing the frontend.

- **`.vercelignore`**  
  Excludes `.env`, `submissions.txt`, and `email_errors.log` from uploads (you use Vercel env vars instead of `.env`; logs aren’t persisted on serverless).

## Steps to deploy

1. **Push the project to Git** (e.g. GitHub).  
   Ensure `vendor/` is in `.gitignore` (it is); Vercel will create it with `composer install` during the build.

2. **Import the repo in Vercel**  
   [vercel.com](https://vercel.com) → Add New → Project → import your repo.  
   Use the repo root as the project root (where `vercel.json` and `api/` live).

3. **Set environment variables in Vercel**  
   In the project: **Settings → Environment Variables**, add the same keys you use in `.env` (at least these for the contact form):

   - `SMTP_HOST` = `smtp.gmail.com`
   - `SMTP_PORT` = `587`
   - `SMTP_USERNAME` = your Gmail address
   - `SMTP_PASSWORD` = your Gmail App Password (16-character)
   - `SMTP_FROM_EMAIL` = same as SMTP_USERNAME (or your sending address)
   - `SMTP_FROM_NAME` = e.g. `Kyle Devs`
   - `SMTP_TO_EMAIL` = address where contact form submissions should go

   Add them for **Production** (and Preview if you want forms to work on preview URLs too).

4. **Deploy**  
   Deploy from the Vercel dashboard or by pushing to the connected branch. The build will run `composer install`, then deploy the static files and the `api/contact.php` function.

## Local development

- **XAMPP:** Keep using the root `contact.php`; `script.js` still uses `contact.php`, which works on Vercel via the rewrite.
- **Optional:** To test the same structure as Vercel locally, you can run:  
  `php -S localhost:8000`  
  from the project root and open `http://localhost:8000`. The rewrite only applies on Vercel, so locally you’d need to use `contact.php` (or temporarily point the form to `api/contact.php` if you serve that path).

## Summary of what you need to do

| Action | Where |
|--------|--------|
| Nothing to move | Repo structure is already set up |
| Add env vars | Vercel project → Settings → Environment Variables |
| Deploy | Push to Git or click Deploy in Vercel |

You do **not** need to change `script.js` or move HTML/CSS/JS; only the PHP contact handler was added under `api/` and wired in `vercel.json`.
