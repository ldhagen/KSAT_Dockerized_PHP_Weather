#!/bin/bash

IMAGE_NAME="ldhagen/ksat-weather-app"
VERSION="2.0.0"
GIT_COMMIT=$(git rev-parse --short HEAD)

# Create multi-architecture builder
docker buildx create --name multiarch --use

# Build for multiple architectures
docker buildx build \
  --platform linux/amd64,linux/arm64 \
  --build-arg GIT_COMMIT=$GIT_COMMIT \
  --build-arg VERSION=$VERSION \
  -t $IMAGE_NAME:$VERSION \
  -t $IMAGE_NAME:latest \
  -t $IMAGE_NAME:$GIT_COMMIT \
  --push .

echo "✅ Multi-arch images pushed to Docker Hub"