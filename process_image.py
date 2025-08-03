import cv2
import numpy as np
import argparse
import sys
import os

def apply_clahe(img):
    if len(img.shape) == 2:
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
        return clahe.apply(img)
    elif len(img.shape) == 3:
        lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)
        l, a, b = cv2.split(lab)
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
        cl = clahe.apply(l)
        merged = cv2.merge((cl, a, b))
        return cv2.cvtColor(merged, cv2.COLOR_LAB2BGR)
    return img

def to_black_white(img):
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    _, bw = cv2.threshold(gray, 127, 255, cv2.THRESH_BINARY)
    return bw

def to_grayscale(img):
    return cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

def enhance_channel(img, channel):
    img = img.copy()
    idx = {'r':2, 'g':1, 'b':0}[channel]
    img[:,:,idx] = np.clip(img[:,:,idx] * 1.5, 0, 255).astype(np.uint8)
    return img

def resize_image(img, width, height):
    return cv2.resize(img, (width, height))

def crop_image(img, x, y, w, h):
    return img[y:y+h, x:x+w]

def zoom_image(img, factor):
    h, w = img.shape[:2]
    nh, nw = int(h*factor), int(w*factor)
    return cv2.resize(img, (nw, nh))

def rotate_image(img, angle):
    h, w = img.shape[:2]
    M = cv2.getRotationMatrix2D((w/2, h/2), angle, 1)
    return cv2.warpAffine(img, M, (w, h))

def main():
    parser = argparse.ArgumentParser(description='Process an image.')
    parser.add_argument('--input', required=True, help='Input image path')
    parser.add_argument('--output', required=True, help='Output image path')
    parser.add_argument('--operation', required=True, choices=['clahe', 'bw', 'grayscale', 'enhance_r', 'enhance_g', 'enhance_b', 'resize', 'crop', 'zoom', 'rotate'])
    parser.add_argument('--width', type=int)
    parser.add_argument('--height', type=int)
    parser.add_argument('--x', type=int)
    parser.add_argument('--y', type=int)
    parser.add_argument('--w', type=int)
    parser.add_argument('--h', type=int)
    parser.add_argument('--factor', type=float)
    parser.add_argument('--angle', type=float)
    args = parser.parse_args()

    if not os.path.exists(args.input):
        print('Input file does not exist', file=sys.stderr)
        sys.exit(1)
    img = cv2.imread(args.input)
    if img is None:
        print('Failed to load image', file=sys.stderr)
        sys.exit(1)

    if args.operation == 'clahe':
        out = apply_clahe(img)
    elif args.operation == 'bw':
        out = to_black_white(img)
    elif args.operation == 'grayscale':
        out = to_grayscale(img)
    elif args.operation == 'enhance_r':
        out = enhance_channel(img, 'r')
    elif args.operation == 'enhance_g':
        out = enhance_channel(img, 'g')
    elif args.operation == 'enhance_b':
        out = enhance_channel(img, 'b')
    elif args.operation == 'resize':
        if args.width is None or args.height is None:
            print('Width and height required for resize', file=sys.stderr)
            sys.exit(1)
        out = resize_image(img, args.width, args.height)
    elif args.operation == 'crop':
        if None in (args.x, args.y, args.w, args.h):
            print('x, y, w, h required for crop', file=sys.stderr)
            sys.exit(1)
        out = crop_image(img, args.x, args.y, args.w, args.h)
    elif args.operation == 'zoom':
        if args.factor is None:
            print('Factor required for zoom', file=sys.stderr)
            sys.exit(1)
        out = zoom_image(img, args.factor)
    elif args.operation == 'rotate':
        if args.angle is None:
            print('Angle required for rotate', file=sys.stderr)
            sys.exit(1)
        out = rotate_image(img, args.angle)
    else:
        print('Unknown operation', file=sys.stderr)
        sys.exit(1)

    # Save output
    if len(out.shape) == 2:
        cv2.imwrite(args.output, out)
    else:
        cv2.imwrite(args.output, cv2.cvtColor(out, cv2.COLOR_BGR2RGB) if out.shape[2] == 3 else out)

if __name__ == '__main__':
    main() 