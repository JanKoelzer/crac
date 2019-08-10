# CrAC - Cranial Angle Calculator

CrAC is a simple image processing tool that detects a predefined number of pixels of a predefined color and interprets the respective points as part of an angle (apex or arm). It then computes and outputs the size of those angles.

## Dependencies/Requirements:
- bash
- PHP 5+ with libgd

## Usage

### Input:
A set of images in png format, located in the subdirectory pics/.
Each file must contain the exact number of pixels of the predefined color.
To process jpg files, run the convert script prior to the next step.

### Run:
Use bash to run "start-all".

### Output:
A CSV file where each line represents an input file and each row represents a computed angle.
