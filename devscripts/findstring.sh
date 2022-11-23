args=("$@")
if [[ "$#" -eq "0" ]]; then
    echo "Need search string."
fi;
echo "Searching for $@ (skipping themes, css) ..."
echo

find . -name '*.*' -print0 | xargs -0 grep "$@" 2>/dev/null | grep -v vendor | grep -v docs | grep -v ^\./js/ | grep -v "Binary file" | grep -v ^\./themes/ | grep -v src/themes | grep -v src/css | grep -v ^\./css
