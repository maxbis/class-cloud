from PIL import Image, ImageDraw

# Define the size of the favicon (64x64 works well for modern browsers)
img_size = (64, 64)
yellow = (255, 255, 0)  # Bright yellow background

# Create a new image with a yellow background
img = Image.new("RGBA", img_size, yellow)
draw = ImageDraw.Draw(img)

# Draw a folded corner in the top-right: a white triangle
fold_size = 16  # Size of the folded corner
fold_points = [
    (img_size[0], 0),                    # top-right corner
    (img_size[0] - fold_size, 0),          # move left
    (img_size[0], fold_size)               # move down
]
fold_color = (255, 255, 255)  # White color for the folded effect
draw.polygon(fold_points, fill=fold_color)

# Optionally, add a simple border (uncomment to enable)
border_color = (200, 200, 0)  # Slightly darker yellow
draw.rectangle([0, 0, img_size[0]-1, img_size[1]-1], outline=border_color)

# Save the image as a favicon.ico file
img.save("favicon.ico", format="ICO")
print("favicon.ico created successfully!")
