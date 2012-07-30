mkdir /media/ramdisk
mount -t tmpfs tmpfs /media/ramdisk
mkdir /media/ramdisk/sqlite
chmod -R 0777 /media/ramdisk/sqlite