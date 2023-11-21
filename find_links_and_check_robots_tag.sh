#!/bin/bash
#noindex finder
#v1.37 thore@homepage-helden.de
#I can not guarantee that this script will work for you or that it will find all links on all pages. Use at your own risk.
debug=false
function scan_page {
    local domain=$1
    local page=$2
    local url="${domain}${page%/}/"
    touch urls_found.txt
    if ! grep -Fxq "$url" urls_found.txt; then
        echo "$url" >> urls_found.txt
        curl -s "$url" | awk -F'href="' '{print $2}' | awk -F'"' '{print $1}' | while read -r sub_page; do
            if [[ $sub_page =~ ^$domain|^/[^/] ]] && ! [[ $sub_page =~ \.[^/]+$|"wishlist_notice=true"|"mailto:"|"tel:"|"wp-json"|"?"|"#" ]] && ! [[ $sub_page =~ "<!\[CDATA\[" ]]; then
                sub_page="${sub_page#$domain}"
                scan_page "$domain" "$sub_page"
            fi
        done
    fi
}
if [ $1 ]; then
    domain=${1%/}
    rm -f robots_results.tsv urls_found.txt noindex_urls.txt
    echo -e "URL\tRobots Tag" > robots_results.tsv
    scan_page "$domain" '/'
    sort -u urls_found.txt -o urls_found.txt
    noindex_count=0
    while read -r url; do
        robots=$(curl -s "$url" | xmllint --html --xpath 'normalize-space(string(/html/head/meta[@name="robots"]/@content))' - 2>/dev/null)
        if [ -z "$robots" ]; then
            robots="EMPTY!"
        fi
        echo -e "$url\t$robots" >> robots_results.tsv
        if [[ $robots == *"noindex"* ]]; then
            echo "$url" >> noindex_urls.txt
            ((noindex_count++))
        fi
    done < urls_found.txt
    total=$(wc -l < urls_found.txt)
    echo "Scan done, found $total unique URLs of which have $noindex_count noindex in their robots tag."
        if [ $noindex_count -gt 0 ]; then
            echo "URLs with noindex:"
            cat noindex_urls.txt
        fi
else
    echo "Usage: ./find_links_and_check_robots_tag.sh https://www.domain.de (without trailing slash!)"
fi
rm -f urls_found.txt noindex_urls.txt
