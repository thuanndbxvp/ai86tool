import os
import shutil
import subprocess
import re

MAIN_DIR = r"D:\Visual-Studio-Pro\ai_suite_landing"
TAN_DIR = os.path.join(MAIN_DIR, "Tan")
DAT_DIR = os.path.join(MAIN_DIR, "Dat")
DAI_DIR = os.path.join(MAIN_DIR, "Dai")

FILES_TO_SYNC = ["index.html", "demo.html", "guide.html", "styles.css", "script.js"]
FILES_BINARY = ["HDSD App đồng bộ_V4.4.pdf", "HD Phân cảnh_V4.4.pdf"]
FOLDERS_TO_SYNC = ["images"]

# Default values from main site
DEFAULT_PHONE = "0948070044"
DEFAULT_ZALO_GROUP = "https://zalo.me/g/mtjofo625"

def ensure_git_repo(target_dir, remote_url):
    if not os.path.exists(os.path.join(target_dir, ".git")):
        print(f"Initializing git repository in {target_dir}...")
        subprocess.run(["git", "init"], cwd=target_dir)
        subprocess.run(["git", "remote", "add", "origin", remote_url], cwd=target_dir)
        subprocess.run(["git", "branch", "-M", "main"], cwd=target_dir)
    else:
        # Update remote url just in case
        subprocess.run(["git", "remote", "set-url", "origin", remote_url], cwd=target_dir)

def sync_site(target_dir, remote_url, phone, zalo_group):
    print(f"Syncing {target_dir}...")
    
    # Ensure target dir exists
    if not os.path.exists(target_dir):
        os.makedirs(target_dir)

    # Sync folders first
    for foldername in FOLDERS_TO_SYNC:
        src_f = os.path.join(MAIN_DIR, foldername)
        dst_f = os.path.join(target_dir, foldername)
        if os.path.exists(src_f):
            if os.path.exists(dst_f):
                shutil.rmtree(dst_f)
            shutil.copytree(src_f, dst_f)

    # Copy and modify files
    for f in FILES_TO_SYNC:
        src = os.path.join(MAIN_DIR, f)
        dst = os.path.join(target_dir, f)
        if os.path.exists(src):
            with open(src, "r", encoding="utf-8") as file:
                content = file.read()
            
            content = content.replace(DEFAULT_PHONE, phone)
            content = content.replace(DEFAULT_ZALO_GROUP, zalo_group)

            # Đổi giá niêm yết cho các bản phụ đại lý (Tan, Dat, Dai)
            if f == "index.html":
                pass

            with open(dst, "w", encoding="utf-8") as file:
                file.write(content)

    # Copy binary files (PDF, etc.) without text processing
    for f in FILES_BINARY:
        src = os.path.join(MAIN_DIR, f)
        dst = os.path.join(target_dir, f)
        if os.path.exists(src):
            shutil.copy2(src, dst)

    # Also copy app_screenshot.png
    if os.path.exists(os.path.join(MAIN_DIR, "app_screenshot.png")):
        shutil.copy2(os.path.join(MAIN_DIR, "app_screenshot.png"), target_dir)
    
    # Git push
    ensure_git_repo(target_dir, remote_url)
    print(f"Pushing {target_dir}...")
    subprocess.run(["git", "add", "."], cwd=target_dir)
    subprocess.run(["git", "commit", "-m", "Sync updates from main site"], cwd=target_dir)
    # Force push to overwrite divergent branches since this is a one-way mirror
    subprocess.run(["git", "push", "-uf", "origin", "main"], cwd=target_dir)

# 1. Tan
sync_site(TAN_DIR, "https://github.com/thuanndbxvp/tan-tool86.git", "0914824924", "https://zalo.me/g/tszyjt724")

# 2. Dat
sync_site(DAT_DIR, "https://github.com/thuanndbxvp/tool86-dat.git", "0973448199", "https://zalo.me/g/qpdnif620")

# 3. Dai
sync_site(DAI_DIR, "https://github.com/thuanndbxvp/tool86-dai.git", "0982689831", "https://zalo.me/g/qpdnif620")

print("All sites synced successfully.")
