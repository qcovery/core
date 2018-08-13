# Module SearchKeys - Specification

This document describes the required behavior of the beluga core module SearchKeys.

## Equalized behavior
VuFind shows in the current version (5) behavior concerning serach fields, which is not simple to understand. For example
searching for "science" with selecting "title" in the pulldown menu searches for "science" in all fields belonging to the "title" search field, defined in searchspecs.yaml.

However, searching for 
```
title: science
```
finds all datasets containing "science" in the index field "title". 

This behavior is nor easily understandable für users. It is a goal of this module to harmonize this, by delivering identical result sets for both searches.

## Search Keys
The modul adds search keys, i.e. the letter keys which specify the search type. For example:

```
per johansson
```

searches for the term "johansson". It is identical to a search 

```
person: johansson
```
or a search for "johansson" with selectioing "person" from the pulldown menu.

### Scope of search keys
* search keys have an scope to the end of the input or until the next search key.
* search keys override the pullwown menu

## colon-based keys
colon based keys (i.e. "title:") work exactly like search keys, except for the colon. They do not refer to solr fields anymore, but indicate search fields. They are log forms of serach keys in all circumstances. This is a deviation from VuFind behavior.

If a colon based key is entered without a suitable search field or search key, the term is interpreted as a raw search input. For example
```
Independence: An inquiry
```
In this serch "independence" followed by the colon is searched for in the index.



