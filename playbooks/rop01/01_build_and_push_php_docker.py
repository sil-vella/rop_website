#!/usr/bin/env python3
"""
Build and push the Reign of Play company PHP/website Docker image.
This image is the general website/backend for the company; the Dutch dashboard is one app within it
(currently includes dsh_php_base_001 and dsh_html_js_css_base_001). No .env is baked in;
provide at runtime via docker-compose environment or env_file.
"""

import os
import subprocess
import sys
from pathlib import Path

# Colors for output
class Colors:
    RED = '\033[0;31m'
    GREEN = '\033[0;32m'
    YELLOW = '\033[1;33m'
    BLUE = '\033[0;34m'
    NC = '\033[0m'  # No Color

# Script is in playbooks/rop01; project root is app_dev
SCRIPT_DIR = Path(__file__).parent.resolve()
PROJECT_ROOT = SCRIPT_DIR.parent.parent

# Company PHP backend in 00_Codebase/php_base_001; dashboard frontend in 00_dashboard/Dutch/dsh_html_js_css_base_001
# Build context: 00_Codebase so Dockerfile can COPY both php_base_001 and 00_dashboard/Dutch/dsh_html_js_css_base_001
CODEBASE_DIR = PROJECT_ROOT / '00_Codebase'
DOCKERFILE_PATH = CODEBASE_DIR / 'php_base_001' / 'Dockerfile'
BUILD_CONTEXT = CODEBASE_DIR

# Configuration (company-wide image name)
DOCKER_USERNAME = os.environ.get('DOCKER_USERNAME', 'silvella')
IMAGE_NAME = os.environ.get('ROP_WEBSITE_IMAGE_NAME', 'rop_website_php')
IMAGE_TAG = os.environ.get('IMAGE_TAG', 'latest')


def check_docker():
    """Check if Docker is running."""
    try:
        subprocess.run(
            ['docker', 'info'],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            check=True,
        )
        return True
    except (subprocess.CalledProcessError, FileNotFoundError):
        return False


def build_and_push():
    """Build and push the Docker image."""
    full_image_name = f"{DOCKER_USERNAME}/{IMAGE_NAME}:{IMAGE_TAG}"

    print(f"\n{Colors.BLUE}Configuration:{Colors.NC}")
    print(f"  Docker Username: {DOCKER_USERNAME}")
    print(f"  Image Name: {IMAGE_NAME}")
    print(f"  Image Tag: {IMAGE_TAG}")
    print(f"  Full Image: {full_image_name}")
    print(f"  Build Context: {BUILD_CONTEXT}")
    print(f"  Dockerfile: {DOCKERFILE_PATH}")
    print()

    if not DOCKERFILE_PATH.exists():
        print(f"{Colors.RED}Error: Dockerfile not found at {DOCKERFILE_PATH}{Colors.NC}")
        return False
    if not BUILD_CONTEXT.exists():
        print(f"{Colors.RED}Error: Build context not found at {BUILD_CONTEXT}{Colors.NC}")
        return False

    # Confirm before proceeding (non-interactive if stdin is not a TTY)
    if sys.stdin.isatty():
        response = input("Proceed with build and push? (y/n): ").strip().lower()
        if response != 'y':
            print(f"{Colors.YELLOW}Build cancelled.{Colors.NC}")
            return False
    else:
        print("Non-interactive mode: Auto-confirming build and push...")

    # Build the Docker image
    print(f"\n{Colors.BLUE}Building Docker image...{Colors.NC}")
    build_cmd = [
        'docker', 'build',
        '-f', str(DOCKERFILE_PATH),
        '-t', full_image_name,
        str(BUILD_CONTEXT),
    ]

    try:
        subprocess.run(build_cmd, check=True)
        print(f"{Colors.GREEN}✓ Docker image built successfully{Colors.NC}")
    except subprocess.CalledProcessError as e:
        print(f"{Colors.RED}✗ Docker build failed{Colors.NC}")
        return False

    # Tag as latest if a different tag was used
    if IMAGE_TAG != 'latest':
        print(f"\n{Colors.BLUE}Tagging as latest...{Colors.NC}")
        latest_tag = f"{DOCKER_USERNAME}/{IMAGE_NAME}:latest"
        subprocess.run(['docker', 'tag', full_image_name, latest_tag], check=True)
        print(f"{Colors.GREEN}✓ Tagged as latest{Colors.NC}")

    # Push to Docker Hub
    print(f"\n{Colors.BLUE}Pushing to Docker Hub...{Colors.NC}")
    try:
        subprocess.run(['docker', 'push', full_image_name], check=True)
        print(f"{Colors.GREEN}✓ Image pushed successfully{Colors.NC}")
    except subprocess.CalledProcessError as e:
        print(f"{Colors.RED}✗ Push failed. Make sure you're logged in: docker login{Colors.NC}")
        return False

    # Push latest tag if different
    if IMAGE_TAG != 'latest':
        print(f"\n{Colors.BLUE}Pushing latest tag...{Colors.NC}")
        latest_tag = f"{DOCKER_USERNAME}/{IMAGE_NAME}:latest"
        try:
            subprocess.run(['docker', 'push', latest_tag], check=True)
            print(f"{Colors.GREEN}✓ Latest tag pushed successfully{Colors.NC}")
        except subprocess.CalledProcessError:
            pass

    return True


def main():
    """Main function."""
    print(f"{Colors.BLUE}=== Reign of Play website PHP — Docker Build and Push ==={Colors.NC}\n")

    if not check_docker():
        print(f"{Colors.RED}Error: Docker is not running. Please start Docker and try again.{Colors.NC}")
        sys.exit(1)

    try:
        success = build_and_push()
        if not success:
            sys.exit(1)
        print(f"\n{Colors.GREEN}=== Build and Push Complete ==={Colors.NC}")
        print(f"Image: {Colors.BLUE}{DOCKER_USERNAME}/{IMAGE_NAME}:{IMAGE_TAG}{Colors.NC}")
        print("Use in docker-compose with the rop_website_php service.")
        print()
    except KeyboardInterrupt:
        print(f"\n{Colors.YELLOW}Interrupted.{Colors.NC}")
        sys.exit(1)
    except Exception as e:
        print(f"\n{Colors.RED}Error: {e}{Colors.NC}")
        sys.exit(1)


if __name__ == '__main__':
    main()
