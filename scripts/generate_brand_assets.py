import os
from PIL import Image, ImageDraw, ImageFont, ImageFilter

def create_brand_assets():
    logo_path = 'logo/Lambang Pendidikan dan Keamanan Siber.png'
    output_dir = 'public/brand'
    os.makedirs(output_dir, exist_ok=True)

    img = Image.open(logo_path)
    # Trim transparency if needed
    bbox = img.getbbox()
    if bbox:
        trimmed_logo = img.crop(bbox)
    else:
        trimmed_logo = img

    # 1. Main logo (clean high-res)
    logo_main = trimmed_logo.resize((512, 512), Image.Resampling.LANCZOS)
    logo_main.save(os.path.join(output_dir, 'logo.png'), 'PNG')
    print("Saved logo.png")

    # 2. Email logo (horizontal or balanced icon)
    # 240x240 for email header
    logo_email = trimmed_logo.resize((240, 240), Image.Resampling.LANCZOS)
    logo_email.save(os.path.join(output_dir, 'logo-email.png'), 'PNG')
    print("Saved logo-email.png")

    # 3. PDF header logo (clean 200x200)
    logo_pdf = trimmed_logo.resize((200, 200), Image.Resampling.LANCZOS)
    logo_pdf.save(os.path.join(output_dir, 'logo-pdf.png'), 'PNG')
    print("Saved logo-pdf.png")

    # 4. Favicon 32x32
    fav_32 = trimmed_logo.resize((32, 32), Image.Resampling.LANCZOS)
    fav_32.save(os.path.join(output_dir, 'favicon-32x32.png'), 'PNG')
    # Also save to public/favicon.ico
    fav_16 = trimmed_logo.resize((16, 16), Image.Resampling.LANCZOS)
    fav_48 = trimmed_logo.resize((48, 48), Image.Resampling.LANCZOS)
    trimmed_logo.save(os.path.join(output_dir, 'favicon.ico'), format='ICO', sizes=[(16,16), (32,32), (48,48)])
    trimmed_logo.save('public/favicon.ico', format='ICO', sizes=[(16,16), (32,32), (48,48)])
    print("Saved favicon.ico and favicon-32x32.png")

    # 5. Apple Touch Icon 180x180
    # Soft rounded squircle clay background
    apple_icon = Image.new('RGBA', (180, 180), (255, 255, 255, 0))
    draw = ImageDraw.Draw(apple_icon)
    # Clay background with subtle gradient/soft rounded rect
    draw.rounded_rectangle([(0, 0), (180, 180)], radius=36, fill=(241, 245, 249, 255))
    # Paste logo in center
    logo_apple = trimmed_logo.resize((130, 130), Image.Resampling.LANCZOS)
    apple_icon.paste(logo_apple, (25, 25), mask=logo_apple.split()[3])
    apple_icon.save(os.path.join(output_dir, 'apple-touch-icon.png'), 'PNG')
    print("Saved apple-touch-icon.png")

    # 6. OpenGraph Image 1200x630
    og = Image.new('RGBA', (1200, 630), (248, 250, 252, 255))
    og_draw = ImageDraw.Draw(og)

    # Decorative clay blobs in background
    # Top-right soft cyber navy blob
    og_draw.ellipse([(850, -150), (1350, 350)], fill=(238, 242, 255, 255))
    # Bottom-left soft warm orange-gold blob
    og_draw.ellipse([(-100, 380), (400, 880)], fill=(255, 247, 237, 255))

    # Center-left clay card
    card_box = [(80, 75), (1120, 555)]
    # Shadow
    shadow = Image.new('RGBA', (1200, 630), (0, 0, 0, 0))
    s_draw = ImageDraw.Draw(shadow)
    s_draw.rounded_rectangle([(84, 85), (1124, 565)], radius=32, fill=(15, 23, 42, 35))
    shadow = shadow.filter(ImageFilter.GaussianBlur(15))
    og = Image.alpha_composite(og, shadow)
    og_draw = ImageDraw.Draw(og)

    # Clay card surface
    og_draw.rounded_rectangle(card_box, radius=32, fill=(255, 255, 255, 250), outline=(226, 232, 240, 255), width=2)

    # Inner embossed highlight line
    og_draw.rounded_rectangle([(82, 77), (1118, 553)], radius=30, outline=(255, 255, 255, 180), width=2)

    # Paste Logo
    logo_og = trimmed_logo.resize((360, 360), Image.Resampling.LANCZOS)
    og.paste(logo_og, (130, 135), mask=logo_og.split()[3])

    # Text side (X: 540)
    # Category badge
    og_draw.rounded_rectangle([(540, 140), (840, 180)], radius=20, fill=(238, 242, 255, 255), outline=(199, 210, 254, 255), width=2)
    og_draw.text((555, 150), "OFFICIAL BUG BOUNTY PLATFORM", fill=(37, 58, 161, 255))

    # Main Title: VulnHunt
    og_draw.text((540, 205), "VulnHunt", fill=(15, 23, 42, 255))
    
    # Subtitle
    og_draw.text((540, 310), "Platform Lomba Bug Bounty Kampus", fill=(37, 58, 161, 255))
    og_draw.text((540, 350), "Universitas Bina Bangsa Getsempena", fill=(71, 85, 105, 255))

    # Features pill badges
    pills = [
        ("Ethical Hacking", (241, 245, 249), (71, 85, 105)),
        ("Responsible Disclosure", (254, 243, 199), (180, 83, 9)),
        ("Verified Triage", (220, 252, 231), (21, 128, 61))
    ]
    px = 540
    for label, bg_color, text_color in pills:
        pw = len(label) * 11 + 30
        og_draw.rounded_rectangle([(px, 440), (px + pw, 480)], radius=20, fill=bg_color, outline=(226, 232, 240), width=1)
        og_draw.text((px + 15, 452), label, fill=text_color)
        px += pw + 15

    og.convert('RGB').save(os.path.join(output_dir, 'og-image.png'), 'PNG')
    print("Saved og-image.png")

if __name__ == '__main__':
    create_brand_assets()
