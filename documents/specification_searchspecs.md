# Module SearchKeys - Specification

This document describes the required behavior of the beluga core module SearchKeys.

## Equalized behavior
VuFind shows in the current version (5) behavior concerning search fields, which is not simple to understand. For example
searching for "science" with selecting "title" in the pulldown menu searches for "science" in all fields belonging to the "title" search field, defined in searchspecs.yaml.

However, searching for 
```
title: science
```
finds all datasets containing "science" in the index field "title". 

This behavior is nor easily understandable für users. It is a goal of this module to harmonize this, by delivering identical result sets for both searches.

## Search Keys
Searchkeys are configurated in searchkeys.ini within a chapter "\[keys-<SearchTypeId>\]" or "\[phrasedKeys-<SearchTypeId>\]"; the latter transforms the search items to a search phrase. The keys are linked to a search type within searchspecs.yaml, e.g.
```
per = Person
```
The modul adds search keys, i.e. the letter keys which specify the search type. For example:
```
per johansson
```
searches for the term "johansson" in the fields specified by the search type Person. It is identical to a search 
```
Person:johansson
```
or a search for "johansson" with selecting "person" from the pulldown menu.

### Scope of search keys
* search keys have a scope until the end of the input or until the next search key.
* search keys override the pullwown menu

## colon-based keys
colon based keys (i.e. "title:") work exactly like search keys, except for the colon. They do not refer to solr fields anymore, but indicate search fields. They are log forms of serach keys in all circumstances. This is a deviation from VuFind behavior.

If a colon based key is entered without a suitable search field or search key, the term is interpreted as a raw search input. For example
```
Independence: An inquiry
```
In this search "independence" followed by the colon is searched for in the index.



